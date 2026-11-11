// shadcn CLI 4.x 的组件统一从 `cn` 包导入类名合并函数，
// 这里再导出一次，业务代码只需 import 一处，不必关心上游来源。
export { cn } from "cn";
