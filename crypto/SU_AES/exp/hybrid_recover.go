package main

import (
	"context"
	"encoding/hex"
	"encoding/json"
	"flag"
	"fmt"
	"math/rand"
	"os"
	"runtime"
	"strconv"
	"sync"
	"sync/atomic"
	"time"
)

var rcon = [11]byte{0x00, 0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40, 0x80, 0x1B, 0x36}
var verbose bool

func xtime(a byte) byte {
	if a&0x80 != 0 {
		return byte(((uint16(a) << 1) ^ 0x1B) & 0xFF)
	}
	return byte((uint16(a) << 1) & 0xFF)
}

func mixColumns(s *[4][4]byte) {
	for i := 0; i < 4; i++ {
		t := s[i][0] ^ s[i][1] ^ s[i][2] ^ s[i][3]
		u := s[i][0]
		s[i][0] ^= t ^ xtime(s[i][0]^s[i][1])
		s[i][1] ^= t ^ xtime(s[i][1]^s[i][2])
		s[i][2] ^= t ^ xtime(s[i][2]^s[i][3])
		s[i][3] ^= t ^ xtime(s[i][3]^u)
	}
}

func shiftRows(s *[4][4]byte) {
	s[0][1], s[1][1], s[2][1], s[3][1] = s[1][1], s[2][1], s[3][1], s[0][1]
	s[0][2], s[1][2], s[2][2], s[3][2] = s[2][2], s[3][2], s[0][2], s[1][2]
	s[0][3], s[1][3], s[2][3], s[3][3] = s[3][3], s[0][3], s[1][3], s[2][3]
}

func subBytes(s *[4][4]byte, sbox *[256]byte) {
	for i := 0; i < 4; i++ {
		for j := 0; j < 4; j++ {
			s[i][j] = sbox[s[i][j]]
		}
	}
}

func addRoundKey(s *[4][4]byte, rk *[44][4]byte, off int) {
	for i := 0; i < 4; i++ {
		w := rk[off+i]
		s[i][0] ^= w[0]
		s[i][1] ^= w[1]
		s[i][2] ^= w[2]
		s[i][3] ^= w[3]
	}
}

func text2matrix(b []byte) [4][4]byte {
	var m [4][4]byte
	for i := 0; i < 16; i++ {
		row := i % 4
		col := i / 4
		m[row][col] = b[i]
	}
	return m
}

func matrix2bytes(m *[4][4]byte) [16]byte {
	var out [16]byte
	idx := 0
	for col := 0; col < 4; col++ {
		for row := 0; row < 4; row++ {
			out[idx] = m[row][col]
			idx++
		}
	}
	return out
}

func expandRoundKeysZeroKey(sbox *[256]byte) [44][4]byte {
	var rk [44][4]byte
	for i := 4; i < 44; i++ {
		if i%4 == 0 {
			prev4 := rk[i-4]
			prev1 := rk[i-1]
			rc := rcon[i/4]
			rk[i][0] = prev4[0] ^ sbox[prev1[1]] ^ rc
			rk[i][1] = prev4[1] ^ sbox[prev1[2]]
			rk[i][2] = prev4[2] ^ sbox[prev1[3]]
			rk[i][3] = prev4[3] ^ sbox[prev1[0]]
		} else {
			prev4 := rk[i-4]
			prev1 := rk[i-1]
			rk[i][0] = prev4[0] ^ prev1[0]
			rk[i][1] = prev4[1] ^ prev1[1]
			rk[i][2] = prev4[2] ^ prev1[2]
			rk[i][3] = prev4[3] ^ prev1[3]
		}
	}
	return rk
}

func encryptBlock(pt []byte, rk *[44][4]byte, sbox *[256]byte) [16]byte {
	st := text2matrix(pt)
	addRoundKey(&st, rk, 0)
	for r := 1; r <= 9; r++ {
		subBytes(&st, sbox)
		shiftRows(&st)
		mixColumns(&st)
		addRoundKey(&st, rk, 4*r)
	}
	subBytes(&st, sbox)
	shiftRows(&st)
	addRoundKey(&st, rk, 40)
	return matrix2bytes(&st)
}

func pkcs7Pad(msg []byte) []byte {
	pad := 16 - (len(msg) % 16)
	if pad == 0 {
		pad = 16
	}
	out := make([]byte, 0, len(msg)+pad)
	out = append(out, msg...)
	for i := 0; i < pad; i++ {
		out = append(out, byte(pad))
	}
	return out
}

func matchECBUptoBlocks(ptPadded []byte, target []byte, rk *[44][4]byte, sbox *[256]byte, needBlocks int) bool {
	totalBlocks := len(ptPadded) / 16
	if needBlocks <= 0 || needBlocks > totalBlocks {
		needBlocks = totalBlocks
	}
	for bi := 0; bi < needBlocks; bi++ {
		i := bi * 16
		ct := encryptBlock(ptPadded[i:i+16], rk, sbox)
		if target[i] != ct[0] || target[i+1] != ct[1] {
			return false
		}
		for j := 0; j < 16; j++ {
			if target[i+j] != ct[j] {
				return false
			}
		}
	}
	return true
}

type Input struct {
	Switch int               `json:"switch"`
	Single map[string]string `json:"single"`
	Pair   map[string]string `json:"pair"`
	Pair2  map[string]string `json:"pair2"`
	Msg1   string            `json:"msg1_hex"`
	Msg2   string            `json:"msg2_hex"`
}

type pairXY struct{ x, y byte }

func findPairsForT(
	ctx context.Context,
	t int,
	used *[256]bool,
	solved *[256]byte,
	knownT bool,
	valT byte,
	knownT1 bool,
	valT1 byte,
	target []byte,
	ptPadded []byte,
	target2 []byte,
	ptPadded2 []byte,
	maxAlt int,
	workers int,
	rng *rand.Rand,
) []pairXY {
	xCands := make([]byte, 0, 256)
	if knownT {
		xCands = append(xCands, valT)
	} else {
		for v := 0; v < 256; v++ {
			if !used[v] {
				xCands = append(xCands, byte(v))
			}
		}
	}
	if rng != nil && len(xCands) > 1 {
		rng.Shuffle(len(xCands), func(i, j int) {
			xCands[i], xCands[j] = xCands[j], xCands[i]
		})
	}

	yCands := make([]byte, 0, 256)
	if knownT1 {
		yCands = append(yCands, valT1)
	} else {
		for v := 0; v < 256; v++ {
			if !used[v] {
				yCands = append(yCands, byte(v))
			}
		}
		if rng != nil && len(yCands) > 1 {
			rng.Shuffle(len(yCands), func(i, j int) {
				yCands[i], yCands[j] = yCands[j], yCands[i]
			})
		}
	}

	var found []pairXY
	var mu sync.Mutex
	var stop int32
	has2 := target2 != nil && ptPadded2 != nil

	tryPair := func(x, y byte) {
		var sbox [256]byte
		for i := t + 2; i < 256; i++ {
			sbox[i] = solved[i]
		}
		for i := 0; i <= t; i++ {
			sbox[i] = x
		}
		sbox[t+1] = y

		rk := expandRoundKeysZeroKey(&sbox)
		if !matchECBUptoBlocks(ptPadded, target, &rk, &sbox, 1) {
			return
		}
		if has2 && !matchECBUptoBlocks(ptPadded2, target2, &rk, &sbox, 1) {
			return
		}
		if matchECBUptoBlocks(ptPadded, target, &rk, &sbox, 0) && (!has2 || matchECBUptoBlocks(ptPadded2, target2, &rk, &sbox, 0)) {
			mu.Lock()
			found = append(found, pairXY{x, y})
			if maxAlt > 0 && len(found) >= maxAlt {
				atomic.StoreInt32(&stop, 1)
			}
			mu.Unlock()
		}
	}

	workerFn := func(start int) {
		for xi := start; xi < len(xCands); xi += workers {
			if atomic.LoadInt32(&stop) != 0 {
				return
			}
			select {
			case <-ctx.Done():
				return
			default:
			}
			x := xCands[xi]
			for yi := 0; yi < len(yCands); yi++ {
				if atomic.LoadInt32(&stop) != 0 {
					return
				}
				y := yCands[yi]
				if y == x {
					continue
				}
				tryPair(x, y)
			}
		}
	}

	var wg sync.WaitGroup
	wg.Add(workers)
	for w := 0; w < workers; w++ {
		go func(id int) {
			defer wg.Done()
			workerFn(id)
		}(w)
	}
	wg.Wait()

	if rng != nil && len(found) > 1 {
		rng.Shuffle(len(found), func(i, j int) {
			found[i], found[j] = found[j], found[i]
		})
	}
	return found
}

func solvePairwiseOnce(
	ctx context.Context,
	ts []int,
	pairCts map[int][]byte,
	pairCts2 map[int][]byte,
	ptPadded []byte,
	ptPadded2 []byte,
	usedBase [256]bool,
	knownBase [256]bool,
	solvedBase [256]byte,
	maxAlt int,
	workers int,
	rng *rand.Rand,
) ([256]byte, bool, error) {
	used := usedBase
	known := knownBase
	solved := solvedBase

	var dfs func(idx int) (bool, error)
	dfs = func(idx int) (bool, error) {
		select {
		case <-ctx.Done():
			return false, nil
		default:
		}
		if idx >= len(ts) {
			return true, nil
		}

		t := ts[idx]
		if known[t] && known[t+1] {
			return dfs(idx + 1)
		}

		target, ok := pairCts[t]
		if !ok {
			return false, fmt.Errorf("missing pair ct for t=%d", t)
		}
		if len(target) != len(ptPadded) {
			return false, fmt.Errorf("pair length mismatch at t=%d (got %d, expected %d)", t, len(target), len(ptPadded))
		}

		var target2 []byte
		if v, ok := pairCts2[t]; ok {
			target2 = v
			if ptPadded2 == nil {
				return false, fmt.Errorf("pair2 ct provided but msg2 not initialized")
			}
			if len(target2) != len(ptPadded2) {
				return false, fmt.Errorf("pair2 length mismatch at t=%d (got %d, expected %d)", t, len(target2), len(ptPadded2))
			}
		}

		var valT, valT1 byte
		if known[t] {
			valT = solved[t]
		}
		if known[t+1] {
			valT1 = solved[t+1]
		}
		alts := findPairsForT(
			ctx, t, &used, &solved,
			known[t], valT, known[t+1], valT1,
			target, ptPadded, target2, ptPadded2,
			maxAlt, workers, rng,
		)
		if verbose {
			fmt.Fprintf(os.Stderr, "t=%d known=(%v,%v) alts=%d\n", t, known[t], known[t+1], len(alts))
		}
		if len(alts) == 0 {
			return false, nil
		}

		for _, p := range alts {
			changedT := false
			changedT1 := false

			if known[t] {
				if p.x != solved[t] {
					continue
				}
			} else {
				if used[p.x] {
					continue
				}
				used[p.x] = true
				known[t] = true
				solved[t] = p.x
				changedT = true
			}

			if known[t+1] {
				if p.y != solved[t+1] || p.y == p.x {
					if changedT {
						used[p.x] = false
						known[t] = false
						solved[t] = 0
					}
					continue
				}
			} else {
				if used[p.y] || p.y == p.x {
					if changedT {
						used[p.x] = false
						known[t] = false
						solved[t] = 0
					}
					continue
				}
				used[p.y] = true
				known[t+1] = true
				solved[t+1] = p.y
				changedT1 = true
			}

			ok, err := dfs(idx + 1)
			if err != nil {
				return false, err
			}
			if ok {
				return true, nil
			}

			if changedT1 {
				used[p.y] = false
				known[t+1] = false
				solved[t+1] = 0
			}
			if changedT {
				used[p.x] = false
				known[t] = false
				solved[t] = 0
			}
		}

		return false, nil
	}

	ok, err := dfs(0)
	return solved, ok, err
}

func solvePairwiseWithRestarts(
	ts []int,
	pairCts map[int][]byte,
	pairCts2 map[int][]byte,
	ptPadded []byte,
	ptPadded2 []byte,
	usedBase [256]bool,
	knownBase [256]bool,
	solvedBase [256]byte,
	maxAlt int,
	workers int,
	restarts int,
	restartParallel int,
	seed int64,
	timeoutSec float64,
) ([256]byte, bool, error) {
	if restarts < 1 {
		restarts = 1
	}
	if restartParallel < 1 {
		restartParallel = 1
	}
	if restartParallel > restarts {
		restartParallel = restarts
	}
	if workers < 1 {
		workers = 1
	}

	workersPerRestart := workers / restartParallel
	if workersPerRestart < 1 {
		workersPerRestart = 1
	}

	var ctx context.Context
	var cancel context.CancelFunc
	if timeoutSec > 0 {
		ctx, cancel = context.WithTimeout(context.Background(), time.Duration(timeoutSec*float64(time.Second)))
	} else {
		ctx, cancel = context.WithCancel(context.Background())
	}
	defer cancel()

	type result struct {
		solved [256]byte
	}
	resultCh := make(chan result, 1)
	errCh := make(chan error, 1)
	jobs := make(chan int)

	var wg sync.WaitGroup
	for wid := 0; wid < restartParallel; wid++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			for rid := range jobs {
				select {
				case <-ctx.Done():
					return
				default:
				}

				rng := rand.New(rand.NewSource(seed + int64(rid+1)*0x5851f42d4c957f2d))
				solved, ok, err := solvePairwiseOnce(
					ctx, ts, pairCts, pairCts2, ptPadded, ptPadded2,
					usedBase, knownBase, solvedBase, maxAlt, workersPerRestart, rng,
				)
				if err != nil {
					select {
					case errCh <- err:
					default:
					}
					cancel()
					return
				}
				if ok {
					select {
					case resultCh <- result{solved: solved}:
					default:
					}
					cancel()
					return
				}
			}
		}()
	}

sendLoop:
	for rid := 0; rid < restarts; rid++ {
		select {
		case <-ctx.Done():
			break sendLoop
		case jobs <- rid:
		}
	}
	close(jobs)
	wg.Wait()

	select {
	case err := <-errCh:
		return [256]byte{}, false, err
	default:
	}
	select {
	case res := <-resultCh:
		return res.solved, true, nil
	default:
		return [256]byte{}, false, nil
	}
}

func emitSolved(solved [256]byte) {
	out := make([]int, 256)
	out[0], out[1] = -1, -1
	for i := 2; i < 256; i++ {
		out[i] = int(solved[i])
	}
	enc, _ := json.Marshal(out)
	fmt.Println(string(enc))
}

func main() {
	var inPath string
	var mode string
	var j int
	var maxAlt int
	var restarts int
	var restartParallel int
	var seed int64
	var timeoutSec float64
	var verboseFlag bool

	flag.StringVar(&inPath, "in", "cts.json", "input json")
	flag.StringVar(&mode, "mode", "dfs", "solver mode")
	flag.IntVar(&j, "j", runtime.NumCPU(), "workers")
	flag.IntVar(&maxAlt, "k", 16, "max alternatives per t in pairwise DFS")
	flag.IntVar(&restarts, "r", 1, "randomized restart count for pairwise DFS")
	flag.IntVar(&restartParallel, "rp", 1, "parallel restart workers")
	flag.Int64Var(&seed, "seed", time.Now().UnixNano(), "random seed for restart shuffling")
	flag.Float64Var(&timeoutSec, "sec", 0, "pairwise search timeout in seconds (0 disables)")
	flag.BoolVar(&verboseFlag, "v", false, "verbose search trace")
	flag.Parse()

	if mode != "dfs" {
		panic("only dfs mode is supported")
	}
	verbose = verboseFlag
	runtime.GOMAXPROCS(j)

	raw, err := os.ReadFile(inPath)
	if err != nil {
		panic(err)
	}

	var inp Input
	if err := json.Unmarshal(raw, &inp); err != nil {
		panic(err)
	}

	SW := inp.Switch
	if SW < 4 || SW > 256 {
		panic("bad switch")
	}

	singleCts := map[int][]byte{}
	for ks, hv := range inp.Single {
		t, err := strconv.Atoi(ks)
		if err != nil {
			panic(fmt.Sprintf("bad single key %q", ks))
		}
		b, err := hex.DecodeString(hv)
		if err != nil {
			panic(fmt.Sprintf("bad single ct at t=%d: %v", t, err))
		}
		singleCts[t] = b
	}

	pairCts := map[int][]byte{}
	for ks, hv := range inp.Pair {
		t, err := strconv.Atoi(ks)
		if err != nil {
			panic(fmt.Sprintf("bad pair key %q", ks))
		}
		b, err := hex.DecodeString(hv)
		if err != nil {
			panic(fmt.Sprintf("bad pair ct at t=%d: %v", t, err))
		}
		pairCts[t] = b
	}

	pairCts2 := map[int][]byte{}
	for ks, hv := range inp.Pair2 {
		t, err := strconv.Atoi(ks)
		if err != nil {
			panic(fmt.Sprintf("bad pair2 key %q", ks))
		}
		b, err := hex.DecodeString(hv)
		if err != nil {
			panic(fmt.Sprintf("bad pair2 ct at t=%d: %v", t, err))
		}
		pairCts2[t] = b
	}

	if inp.Msg1 == "" {
		panic("msg1_hex is required")
	}
	msg1, err := hex.DecodeString(inp.Msg1)
	if err != nil {
		panic(fmt.Sprintf("bad msg1_hex: %v", err))
	}
	ptPadded := pkcs7Pad(msg1)

	var ptPadded2 []byte
	if len(pairCts2) > 0 {
		if inp.Msg2 == "" {
			panic("msg2_hex is required when pair2 is provided")
		}
		msg2, err := hex.DecodeString(inp.Msg2)
		if err != nil {
			panic(fmt.Sprintf("bad msg2_hex: %v", err))
		}
		ptPadded2 = pkcs7Pad(msg2)
	}

	singleStart := 256
	for t := range singleCts {
		if t < 0 || t > 255 {
			panic(fmt.Sprintf("single t out of range: %d", t))
		}
		if t < singleStart {
			singleStart = t
		}
	}

	var solved [256]byte
	var used [256]bool
	var known [256]bool
	singleRng := rand.New(rand.NewSource(seed ^ 0x5851f42d4c957f2d))

	if singleStart < 256 {
		for t := 255; t >= singleStart; t-- {
			target, ok := singleCts[t]
			if !ok {
				panic(fmt.Sprintf("missing single ct for t=%d", t))
			}
			if len(target) != len(ptPadded) {
				panic(fmt.Sprintf("single length mismatch at t=%d (got %d, expected %d)", t, len(target), len(ptPadded)))
			}

			cands := make([]byte, 0, 256)
			for v := 0; v < 256; v++ {
				if !used[v] {
					cands = append(cands, byte(v))
				}
			}

			alts := make([]byte, 0, 8)
			for _, x := range cands {
				var sbox [256]byte
				for i := t + 1; i < 256; i++ {
					sbox[i] = solved[i]
				}
				for i := 0; i <= t; i++ {
					sbox[i] = x
				}
				rk := expandRoundKeysZeroKey(&sbox)
				if !matchECBUptoBlocks(ptPadded, target, &rk, &sbox, 1) {
					continue
				}
				if matchECBUptoBlocks(ptPadded, target, &rk, &sbox, 0) {
					alts = append(alts, x)
				}
			}
			if len(alts) == 0 {
				panic(fmt.Sprintf("single recover failed at t=%d", t))
			}
			if len(alts) > 1 {
				singleRng.Shuffle(len(alts), func(i, j int) {
					alts[i], alts[j] = alts[j], alts[i]
				})
				if verbose {
					fmt.Fprintf(os.Stderr, "single t=%d alts=%d\n", t, len(alts))
				}
			}
			x := alts[0]
			solved[t] = x
			used[x] = true
			known[t] = true
		}
	}

	start := SW - 1
	if start%2 == 1 {
		start--
	}
	if start < 2 {
		panic("switch too small")
	}

	ts := make([]int, 0, (start/2)+1)
	for t := start; t >= 2; t -= 2 {
		if _, ok := pairCts[t]; !ok {
			panic(fmt.Sprintf("missing pair ct for t=%d", t))
		}
		ts = append(ts, t)
	}

	solvedPair, ok, err := solvePairwiseWithRestarts(
		ts, pairCts, pairCts2, ptPadded, ptPadded2,
		used, known, solved, maxAlt, j, restarts, restartParallel, seed, timeoutSec,
	)
	if err != nil {
		panic(err)
	}
	if !ok {
		panic("pairwise dfs failed (increase -k/-r/-rp or lower SWITCH)")
	}
	emitSolved(solvedPair)
}
