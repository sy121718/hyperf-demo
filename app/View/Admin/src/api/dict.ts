import { http } from "@/lib/http";
import type {
  DictItemOption,
  DictPayload,
  DictRow,
  DictSaved,
} from "@/types/dict";
import type { ListQuery } from "@/lib/list";

/** 字典接口返回的是整棵树数组（非分页），由 DictResponse::collection 裁剪 */
export function getDictTree(params: ListQuery) {
  return http.get<DictRow[]>("/dict/tree", { params });
}

export function getDictInfo(id: number) {
  return http.get<DictRow>(`/dict/info/${id}`);
}

export function getDictItems(dictId: number) {
  return http.get<DictItemOption[]>(`/dict/items/${dictId}`);
}

export function createDict(payload: DictPayload) {
  return http.post<DictSaved>("/dict/create", payload);
}

export function updateDict(id: number, payload: DictPayload) {
  return http.put<DictSaved>(`/dict/update/${id}`, payload);
}

export function deleteDict(id: number) {
  return http.delete<null>(`/dict/delete/${id}`);
}
