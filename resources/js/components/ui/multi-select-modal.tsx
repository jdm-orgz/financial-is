import * as React from "react"
import { Check, ChevronsUpDown, Search } from "lucide-react"

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Checkbox } from "@/components/ui/checkbox"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"

interface Option {
  id: string
  name: string
}

interface MultiSelectModalProps {
  options: Option[]
  selected: string[]
  onChange: (selected: string[]) => void
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  disabled?: boolean
}

export function MultiSelectModal({
  options,
  selected,
  onChange,
  placeholder = "Select items...",
  searchPlaceholder = "Search items...",
  emptyText = "No items found.",
  disabled = false,
}: MultiSelectModalProps) {
  const [open, setOpen] = React.useState(false)
  const [searchQuery, setSearchQuery] = React.useState("")

  const filteredOptions = options.filter((option) =>
    option.name.toLowerCase().includes(searchQuery.toLowerCase())
  )

  const handleSelect = (optionId: string, isChecked: boolean) => {
    if (isChecked) {
      onChange([...selected, optionId])
    } else {
      onChange(selected.filter((id) => id !== optionId))
    }
  }

  const handleSelectAll = (isChecked: boolean) => {
    if (isChecked) {
        // Only select from the filtered options, or select all filtered ones that aren't already selected
        const newSelected = new Set(selected);
        filteredOptions.forEach(opt => newSelected.add(opt.id));
        onChange(Array.from(newSelected));
    } else {
        // Deselect only the filtered options
        const filteredIds = new Set(filteredOptions.map(opt => opt.id));
        onChange(selected.filter((id) => !filteredIds.has(id)));
    }
  }

  const allFilteredSelected = filteredOptions.length > 0 && filteredOptions.every(opt => selected.includes(opt.id))

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className="w-full justify-between font-normal"
          disabled={disabled}
        >
          {selected.length > 0
            ? `${selected.length} item${selected.length > 1 ? "s" : ""} selected`
            : placeholder}
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{placeholder}</DialogTitle>
        </DialogHeader>
        <div className="flex items-center border-b px-3 pb-3">
          <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
          <input
            className="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
            placeholder={searchPlaceholder}
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
        <div className="max-h-[300px] overflow-y-auto p-1">
          {filteredOptions.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted-foreground">
              {emptyText}
            </p>
          ) : (
            <div className="space-y-4 pt-2">
                <div className="flex items-center space-x-2 px-2">
                    <Checkbox 
                        id="select-all" 
                        checked={allFilteredSelected}
                        onCheckedChange={handleSelectAll}
                    />
                    <Label htmlFor="select-all" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer">
                        Select All
                    </Label>
                </div>
              {filteredOptions.map((option) => (
                <div key={option.id} className="flex items-center space-x-2 px-2">
                  <Checkbox
                    id={option.id}
                    checked={selected.includes(option.id)}
                    onCheckedChange={(checked) =>
                      handleSelect(option.id, checked as boolean)
                    }
                  />
                  <Label
                    htmlFor={option.id}
                    className="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                  >
                    {option.name}
                  </Label>
                </div>
              ))}
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  )
}
