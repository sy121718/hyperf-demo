/**
 * 格式化工具。
 *
 * 后端时间统一按 UTC 写入、形如 `2026-11-11 05:53:43.389`，
 * 这里集中做一次解析与本地时区转换，组件里不再各自 new Date。
 */

/** 空值占位，表格里统一显示这个而不是空白 */
export const EMPTY_TEXT = "-";

/**
 * 解析后端时间串。
 *
 * 后端给的是不带时区的 UTC 字符串，直接 `new Date("2026-11-11 05:53:43.389")`
 * 各浏览器解析不一致（Safari 直接 NaN），所以手动拆分字段再按 UTC 构造。
 */
function parseServerTime(value: string): Date | null {
  const matched = value
    .trim()
    .match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,3}))?$/);
  if (!matched) {
    return null;
  }

  const [, year, month, day, hour, minute, second, ms] = matched;
  return new Date(
    Date.UTC(
      Number(year),
      Number(month) - 1,
      Number(day),
      Number(hour),
      Number(minute),
      Number(second),
      Number(ms ?? 0),
    ),
  );
}

function pad(value: number): string {
  return String(value).padStart(2, "0");
}

/** 时间串 -> 本地时区 `YYYY-MM-DD HH:mm:ss`，无法解析时原样返回 */
export function formatDateTime(value: string | null | undefined): string {
  if (!value) {
    return EMPTY_TEXT;
  }

  const date = parseServerTime(value);
  if (!date) {
    return value;
  }

  return (
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ` +
    `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
  );
}

/** 时间串 -> 本地时区 `YYYY-MM-DD` */
export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return EMPTY_TEXT;
  }

  const date = parseServerTime(value);
  if (!date) {
    return value;
  }

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/**
 * 本地日期控件值（`YYYY-MM-DD`）转回后端可接受的 UTC 时间串。
 *
 * 后端按 UTC 存时间，日期筛选传的是「用户视角的某一天」，
 * 这里只负责把日期补成当天 00:00:00，不做时区偏移——
 * 后端 `where('x', '>=', '2026-11-12')` 会按字面比较。
 */
export function toDateParam(value: string | null | undefined): string | undefined {
  return value ? value : undefined;
}

/** 文件大小：B / KB / MB / GB，保留一位小数 */
export function formatFileSize(bytes: number | null | undefined): string {
  if (bytes === null || bytes === undefined) {
    return EMPTY_TEXT;
  }
  if (bytes < 1024) {
    return `${bytes} B`;
  }

  const units = ["KB", "MB", "GB", "TB"];
  let size = bytes / 1024;
  let index = 0;
  while (size >= 1024 && index < units.length - 1) {
    size /= 1024;
    index += 1;
  }

  return `${size.toFixed(1)} ${units[index]}`;
}

/** 毫秒耗时 -> 便于阅读的文本 */
export function formatDuration(ms: number | null | undefined): string {
  if (ms === null || ms === undefined) {
    return EMPTY_TEXT;
  }
  return ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(2)} s`;
}

/** 0 / 1 -> 启用 / 禁用 */
export function formatStatus(status: number | null | undefined): string {
  if (status === 1) {
    return "启用";
  }
  return status === 0 ? "禁用" : EMPTY_TEXT;
}
