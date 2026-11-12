import type { Switch } from "@/types/api";

/** 字典项（DictResponse 的 items 子集合） */
export interface DictItem {
  id: number;
  dict_id: number;
  item_label: string;
  item_value: string;
  sort_order: number;
  status: Switch;
}

/** 字典行（DictResponse::listFields，带 items） */
export interface DictRow {
  id: number;
  dict_name: string;
  dict_code: string;
  dict_type: string;
  status: Switch;
  create_time: string | null;
  items?: DictItem[];
}

export interface DictSaved {
  id: number;
  dict_code: string;
  dict_name: string;
}

export interface DictPayload {
  dict_name?: string;
  dict_code?: string;
  dict_type?: string;
  status?: Switch;
}

/** GET /api/dict/items/{dict_id} 的返回项 */
export interface DictItemOption {
  id: number;
  item_label: string;
  item_value: string;
  sort_order: number;
}
