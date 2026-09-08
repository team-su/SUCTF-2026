import os

CATEGORIES = ['AI', 'crypto', 'misc', 'pwn', 're', 'web']

def find_writeup_path(base_path):
    possible_paths = [
        os.path.join(base_path, "writeup"),
        os.path.join(base_path, "解题赛模板", "writeup"),
        os.path.join(base_path, "WP"),
        os.path.join(base_path, "write-up")
    ]
    for p in possible_paths:
        if os.path.isdir(p):
            return p
    return None

print("| catalog | **Game Name** | Writeup Link |")
print("| ------- | ------------- | ------------ |")

results = []
for cat in CATEGORIES:
    if not os.path.exists(cat):
        continue
    
    challenges = sorted([e for e in os.listdir(cat) if os.path.isdir(os.path.join(cat, e)) and not e.startswith('.')])
    
    for chall in challenges:
        chall_path = os.path.join(cat, chall)
        wp_dir = find_writeup_path(chall_path)
        
        if wp_dir:
            results.append(f"| {cat} | {chall} | [./{wp_dir}](./{wp_dir}) |")

for line in results:
    print(line)