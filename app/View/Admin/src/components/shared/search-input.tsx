import { useEffect, useState } from "react";
import { Search } from "lucide-react";

import { Input } from "@rbac/ui/components/ui/input";

/**
 * 列表搜索框。
 *
 * 输入停 300ms 才触发查询，避免每敲一个字打一次接口；
 * 外部值变化（如重置）时同步回输入框。
 */
export interface SearchInputProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  className?: string;
}

export function SearchInput({
  value,
  onChange,
  placeholder = "搜索",
  className = "w-60",
}: SearchInputProps) {
  const [draft, setDraft] = useState(value);

  useEffect(() => {
    setDraft(value);
  }, [value]);

  useEffect(() => {
    if (draft === value) {
      return;
    }
    const timer = window.setTimeout(() => onChange(draft), 300);
    return () => window.clearTimeout(timer);
  }, [draft, value, onChange]);

  return (
    <div className={`relative ${className}`}>
      <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground" />
      <Input
        value={draft}
        placeholder={placeholder}
        className="pl-8"
        onChange={(event) => setDraft(event.target.value)}
      />
    </div>
  );
}
