#!/usr/bin/env bash
# 重构验证脚本：语法检查 + 全量单元测试
# 用法：
#   scripts/verify-refactor.sh syntax   # 只做 php -l 语法检查（快，秒级）
#   scripts/verify-refactor.sh test     # 只跑全量测试
#   scripts/verify-refactor.sh all      # 语法 + 测试
set -uo pipefail
cd "$(dirname "$0")/.." || exit 1

PHP_BIN="${PHP_BIN:-/home/sky/.vfox/cache/php/v-8.5.12/php-8.5.12/bin/php}"
MODE="${1:-all}"

run_syntax() {
    local fail=0
    while IFS= read -r f; do
        if ! "$PHP_BIN" -l "$f" >/dev/null 2>&1; then
            echo "语法错误: $f"
            "$PHP_BIN" -l "$f"
            fail=1
        fi
    done < <(find app/Service app/Common app/Model test/Cases -name '*.php')
    if [ "$fail" -eq 0 ]; then
        echo "语法检查通过"
    fi
    return "$fail"
}

run_test() {
    set -a
    # shellcheck disable=SC1091
    . ./.env 2>/dev/null
    set +a
    "$PHP_BIN" vendor/bin/co-phpunit --prepend test/bootstrap.php 2>&1 | tail -20
    # co-phpunit 即使有 risky 也返回 0，这里以 "Tests:" 行的 OK/Failures 判断
}

case "$MODE" in
    syntax) run_syntax ;;
    test)   run_test ;;
    all)
        run_syntax || exit 1
        run_test
        ;;
    *)
        echo "未知模式: $MODE（可选 syntax / test / all）"
        exit 1
        ;;
esac
