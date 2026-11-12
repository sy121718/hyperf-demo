import { http } from "@/lib/http";
import type {
  MenuDetail,
  MenuPayload,
  MenuSaved,
  MenuTreeNode,
} from "@/types/menu";
import type { ListQuery } from "@/lib/list";

export function getMenuTree(params: ListQuery) {
  return http.get<MenuTreeNode[]>("/menu/tree", { params });
}

export function getMenuInfo(id: number) {
  return http.get<MenuDetail>(`/menu/info/${id}`);
}

export function createMenu(payload: MenuPayload) {
  return http.post<MenuSaved>("/menu/create", payload);
}

export function updateMenu(id: number, payload: MenuPayload) {
  return http.put<MenuSaved>(`/menu/update/${id}`, payload);
}

export function deleteMenu(id: number) {
  return http.delete<null>(`/menu/delete/${id}`);
}

export function batchDeleteMenus(ids: number[]) {
  return http.post<{ deleted: number }>("/menu/batch-delete", { ids });
}
