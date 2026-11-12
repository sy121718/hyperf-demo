/** 列表每页条数默认值，与后端 BaseRequestDto::pageSize() 的默认保持一致 */
export const DEFAULT_PAGE_SIZE = 10;

/** 后端允许的每页条数上限（BaseRequestDto 校验 max:100） */
export const MAX_PAGE_SIZE = 100;

/** 分页可选条数 */
export const PAGE_SIZE_OPTIONS = [10, 20, 50, 100] as const;
