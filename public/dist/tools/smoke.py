import concurrent.futures
import json
import os
from pathlib import Path
import re
import signal
import socket
import subprocess
import time
import urllib.error
import urllib.request

work = Path(__file__).resolve().parents[1]
binary = work / "output/hyperf-demo"
run = work / "run"
# 清空 PATH 和环境，不借用本机 PHP、Node.js、扩展或业务密钥。
environment = {"PATH": "/nonexistent", "HOME": str(run), "LANG": "C.UTF-8"}
opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))


def request(path, data=None, method=None):
    payload = None if data is None else json.dumps(data).encode()
    req = urllib.request.Request(
        "http://127.0.0.1:19501" + path,
        data=payload, method=method,
        headers={"Content-Type": "application/json"},
    )
    try:
        response = opener.open(req, timeout=5)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, response.headers, response.read().decode()


# 先确认端口空闲，避免请求到其他服务。
with socket.socket() as listener:
    listener.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    listener.bind(("127.0.0.1", 19501))

print("COMMAND", binary, "start", flush=True)
with (work / "server.log").open("w") as log:
    process = subprocess.Popen(
        [str(binary), "start"], cwd=run, env=environment,
        stdout=log, stderr=subprocess.STDOUT, start_new_session=True,
    )
    try:
        for attempt in range(120):
            if process.poll() is not None:
                raise RuntimeError("服务提前退出，退出码 " + str(process.returncode))
            try:
                status, headers, frontend = request("/")
                if status == 200 and 'id="root"' in frontend:
                    break
            except (OSError, ValueError):
                pass
            time.sleep(0.5)
        else:
            raise TimeoutError("服务在 60 秒内未就绪")

        assert "text/html" in headers.get("Content-Type", "")
        assert headers.get("Cache-Control") == "no-cache"
        status, _, admin = request("/admin/login")
        assert status == 200 and 'id="root"' in admin
        for path in ["/login", "/notice/12"]:
            assert request(path)[2] == frontend, path
        for path in ["/admin", "/admin/", "/admin/system/admin"]:
            assert request(path)[2] == admin, path
        assert request("/login", method="HEAD")[2] == ""
        print("FRONTEND_AND_ADMIN_HISTORY PASS", flush=True)

        # 实际请求两站点的脚本、样式，不能只验证 HTML 空壳。
        for html in [frontend, admin]:
            assets = re.findall(r'(?:src|href)="([^\"]+\.(?:js|css))"', html)
            assert assets, html
            for asset in assets:
                status, headers, content = request(asset)
                assert status == 200 and content, asset
                assert "text/html" not in headers.get("Content-Type", ""), asset
                print("ASSET", asset, status, len(content), flush=True)
        for path in ["/assets/missing.js", "/admin/assets/missing.js", "/dist/output/hyperf-demo", "/dist/tools/build.php", "/.env"]:
            assert request(path)[0] == 404, path
        assert request("/api/unknown")[0] == 404
        print("STATIC_AND_API_BOUNDARIES PASS", flush=True)

        status, _, body = request("/api/auth/profile")
        body = json.loads(body)
        print("UNAUTHENTICATED", status, body, flush=True)
        assert body.get("code") == 401, body
        status, _, body = request("/api/auth/login", {})
        body = json.loads(body)
        print("LOGIN_VALIDATION", status, body, flush=True)
        assert body.get("success") is False and body.get("code") in (400, 422), body
        with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
            responses = list(pool.map(lambda _: request("/"), range(24)))
        assert all(status == 200 and body == frontend for status, _, body in responses)
        print("CONCURRENT_ROOT", len(responses), "PASS", flush=True)
        print("SMOKE PASS", flush=True)
    finally:
        if process.poll() is None:
            process.send_signal(signal.SIGTERM)
            try:
                process.wait(timeout=15)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid, signal.SIGKILL)
                process.wait(timeout=5)
        print("SERVER_EXIT", process.returncode, flush=True)

# 确认测试实例没有遗留监听进程。
with socket.socket() as listener:
    assert listener.connect_ex(("127.0.0.1", 19501)) != 0, "测试端口未释放"
