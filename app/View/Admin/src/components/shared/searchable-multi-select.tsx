import { useMemo, useState } from "react";
import { Check, ChevronsUpDown, X } from "lucide-react";

import { Badge } from "@rbac/ui/components/ui/badge";
import { Button } from "@rbac/ui/components/ui/button";
import { Input } from "@rbac/ui/components/ui/input";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@rbac/ui/components/ui/popover";
import { cn } from "@rbac/ui/lib/utils";

/**
 * 可搜索的多选。
 *
 * 权限码、菜单 ID 这类候选可能上百条，原生 select multiple 既难用也无法搜索，
 * 这里用 Popover + 过滤输入替代。选中项以 Badge 形式回显在触发器里。
 */
export interface MultiSelectOption {
  value: string;
  label: string;
  /** 补充说明，如权限码对应的接口路径 */
  hint?: string;
}

export interface SearchableMultiSelectProps {
  options: MultiSelectOption[];
  value: string[];
  onChange: (value: string[]) => void;
  placeholder?: string;
  /** 触发器里最多展示几个已选项，其余折叠成 +N */
  maxVisible?: number;
  disabled?: boolean;
}

export function SearchableMultiSelect({
  options,
  value,
  onChange,
  placeholder = "请选择",
  maxVisible = 3,
  disabled = false,
}: SearchableMultiSelectProps) {
  const [open, setOpen] = useState(false);
  const [keyword, setKeyword] = useState("");

  const selectedSet = useMemo(() => new Set(value), [value]);

  const filtered = useMemo(() => {
    const text = keyword.trim().toLowerCase();
    if (!text) {
      return options;
    }
    return options.filter(
      (option) =>
        option.value.toLowerCase().includes(text) || option.label.toLowerCase().includes(text),
    );
  }, [options, keyword]);

  const toggle = (optionValue: string) => {
    if (selectedSet.has(optionValue)) {
      onChange(value.filter((item) => item !== optionValue));
    } else {
      onChange([...value, optionValue]);
    }
  };

  const visible = value.slice(0, maxVisible);
  const restCount = value.length - visible.length;
  const labelOf = (optionValue: string) =>
    options.find((option) => option.value === optionValue)?.label ?? optionValue;

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          type="button"
          variant="outline"
          disabled={disabled}
          className="h-auto min-h-8 w-full justify-between py-1 font-normal"
        >
          <span className="flex flex-wrap items-center gap-1">
            {value.length === 0 ? (
              <span className="text-muted-foreground">{placeholder}</span>
            ) : (
              <>
                {visible.map((item) => (
                  <Badge key={item} variant="secondary" className="gap-1">
                    {labelOf(item)}
                  </Badge>
                ))}
                {restCount > 0 ? <Badge variant="outline">+{restCount}</Badge> : null}
              </>
            )}
          </span>
          <ChevronsUpDown className="text-muted-foreground" />
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-(--radix-popover-trigger-width) p-0" align="start">
        <div className="border-b p-2">
          <Input
            autoFocus
            value={keyword}
            placeholder="输入关键字过滤"
            onChange={(event) => setKeyword(event.target.value)}
          />
        </div>

        <div className="max-h-64 overflow-y-auto p-1">
          {filtered.length === 0 ? (
            <p className="p-3 text-center text-sm text-muted-foreground">没有匹配项</p>
          ) : (
            filtered.map((option) => {
              const checked = selectedSet.has(option.value);
              return (
                <button
                  key={option.value}
                  type="button"
                  onClick={() => toggle(option.value)}
                  className={cn(
                    "flex w-full items-start gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent",
                    checked && "bg-accent/60",
                  )}
                >
                  <span className="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded border">
                    {checked ? <Check className="size-3" /> : null}
                  </span>
                  <span className="min-w-0">
                    <span className="block truncate">{option.label}</span>
                    {option.hint ? (
                      <span className="block truncate text-xs text-muted-foreground">{option.hint}</span>
                    ) : null}
                  </span>
                </button>
              );
            })
          )}
        </div>

        {value.length > 0 ? (
          <div className="flex items-center justify-between border-t p-2">
            <span className="text-xs text-muted-foreground">已选 {value.length} 项</span>
            <Button type="button" variant="ghost" size="sm" onClick={() => onChange([])}>
              <X />
              清空
            </Button>
          </div>
        ) : null}
      </PopoverContent>
    </Popover>
  );
}
