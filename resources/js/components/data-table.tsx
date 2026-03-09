
"use client"
import * as React from "react"
import { useState, useEffect } from "react"
import { usePage } from "@inertiajs/react"
import {
  closestCenter,
  DndContext,
  KeyboardSensor,
  MouseSensor,
  TouchSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
  type UniqueIdentifier,
} from "@dnd-kit/core"
import { restrictToVerticalAxis } from "@dnd-kit/modifiers"
import {
  arrayMove,
  SortableContext,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import {
  IconChevronDown,
  IconChevronLeft,
  IconChevronRight,
  IconChevronsLeft,
  IconChevronsRight,
  IconCircleCheckFilled,
  IconDotsVertical,
  IconGripVertical,
  IconLayoutColumns,
  IconLoader,
  IconPlus,
  IconSearch,
  IconFilter,
  IconX,
} from "@tabler/icons-react"
import {
  ColumnDef,
  ColumnFiltersState,
  flexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  Row,
  SortingState,
  useReactTable,
  VisibilityState,
} from "@tanstack/react-table"
import { toast } from "sonner"
import { z } from "zod"

import { useIsMobile } from "@/hooks/use-mobile"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerDescription,
  DrawerFooter,
  DrawerHeader,
  DrawerTitle,
  DrawerTrigger,
} from "@/components/ui/drawer"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Separator } from "@/components/ui/separator"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs"
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from "@/components/ui/alert"
import { AlertCircle } from "lucide-react"

// Ticket schema based on your backend
export const ticketSchema = z.object({
  id: z.number(),
  ticket_number: z.string(),
  subject: z.string(),
  description: z.string(),
  category: z.string(),
  priority: z.string(),
  status: z.string(),
  assignment_type: z.string().optional(),
  assigned_department_id: z.number().nullable(),
  assigned_resolver_id: z.number().nullable(),
  due_date: z.string().nullable(),
  created_at: z.string(),
  updated_at: z.string(),
  requester_name: z.string(),
  requester_email: z.string(),
  assigned_department: z.object({
    id: z.number(),
    name: z.string(),
    slug: z.string(),
  }).nullable(),
  assigned_resolver: z.object({
    id: z.number(),
    name: z.string(),
    email: z.string(),
  }).nullable(),
})

interface DataTableProps {
  tickets: z.infer<typeof ticketSchema>[]
  loading: boolean
  error?: string
}

interface PageProps {
  auth: {
    user: any
  }
  filters?: {
    status?: string
    priority?: string
    category?: string
    search?: string
  }
  [key: string]: any
}

//  for drag handle
function DragHandle({ id }: { id: number }) {
  const { attributes, listeners } = useSortable({
    id,
  })

  return (
    <Button
      {...attributes}
      {...listeners}
      variant="ghost"
      size="icon"
      className="text-muted-foreground size-7 hover:bg-transparent"
    >
      <IconGripVertical className="text-muted-foreground size-3" />
      <span className="sr-only">Drag to reorder</span>
    </Button>
  )
}

const columns: ColumnDef<z.infer<typeof ticketSchema>>[] = [
  {
    id: "drag",
    header: () => null,
    cell: ({ row }) => <DragHandle id={row.original.id} />,
  },
  {
    id: "select",
    header: ({ table }) => (
      <div className="flex items-center justify-center">
        <Checkbox
          checked={
            table.getIsAllPageRowsSelected() ||
            (table.getIsSomePageRowsSelected() && "indeterminate")
          }
          onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
          aria-label="Select all"
        />
      </div>
    ),
    cell: ({ row }) => (
      <div className="flex items-center justify-center">
        <Checkbox
          checked={row.getIsSelected()}
          onCheckedChange={(value) => row.toggleSelected(!!value)}
          aria-label="Select row"
        />
      </div>
    ),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: "ticket_number",
    header: "Ticket #",
    cell: ({ row }) => {
      return <TableCellViewer item={row.original} />
    },
    enableHiding: false,
  },
  {
    accessorKey: "subject",
    header: "Subject",
    cell: ({ row }) => (
      <div className="max-w-xs truncate">
        {row.original.subject}
      </div>
    ),
  },
  {
    accessorKey: "category",
    header: "Category",
    cell: ({ row }) => (
      <div className="w-32">
        <Badge variant="outline" className="text-muted-foreground px-1.5">
          {row.original.category}
        </Badge>
      </div>
    ),
  },
  {
    accessorKey: "priority",
    header: "Priority",
    cell: ({ row }) => {
      const priority = row.original.priority.toLowerCase()
      let variant: "default" | "secondary" | "destructive" | "outline" = "outline"
      
      if (priority === "high") variant = "destructive"
      if (priority === "medium") variant = "default"
      if (priority === "low") variant = "secondary"

      return (
        <Badge variant={variant} className="px-1.5">
          {row.original.priority}
        </Badge>
      )
    },
  },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) => (
      <Badge variant="outline" className="text-muted-foreground px-1.5">
        {row.original.status === "resolved" ? (
          <IconCircleCheckFilled className="fill-green-500 dark:fill-green-400" />
        ) : (
          <IconLoader />
        )}
        {row.original.status}
      </Badge>
    ),
  },
  {
    accessorKey: "assignment_type",
    header: "Assignment Type",
    cell: ({ row }) => {
      const assignmentType = row.original.assignment_type || 'individual';
      return (
        <Badge 
          variant={assignmentType === 'group' ? 'secondary' : 'outline'}
          className="capitalize"
        >
          {assignmentType}
          {assignmentType === 'group' && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                // Handle group members view
                console.log('View group members for ticket:', row.original.id);
              }}
              className="ml-2 text-xs text-muted-foreground hover:text-foreground"
            >
              👥
            </button>
          )}
        </Badge>
      )
    },
  },
  {
    accessorKey: "assigned_resolver",
    header: "Assigned To",
    cell: ({ row }) => {
      const resolver = row.original.assigned_resolver
      return resolver ? resolver.name : "Unassigned"
    },
  },
  {
    accessorKey: "due_date",
    header: () => <div className="w-full text-right">Due Date</div>,
    cell: ({ row }) => (
      <div className="text-right">
        {row.original.due_date ? new Date(row.original.due_date).toLocaleDateString() : "No due date"}
      </div>
    ),
  },
  {
    id: "actions",
    cell: ({ row }) => (
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            className="data-[state=open]:bg-muted text-muted-foreground flex size-8"
            size="icon"
          >
            <IconDotsVertical />
            <span className="sr-only">Open menu</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-32">
          <DropdownMenuItem>Edit</DropdownMenuItem>
          <DropdownMenuItem>Assign</DropdownMenuItem>
          <DropdownMenuItem>View Details</DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem variant="destructive">Close Ticket</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    ),
  },
]

function DraggableRow({ row }: { row: Row<z.infer<typeof ticketSchema>> }) {
  const { transform, transition, setNodeRef, isDragging } = useSortable({
    id: row.original.id,
  })

  return (
    <TableRow
      data-state={row.getIsSelected() && "selected"}
      data-dragging={isDragging}
      ref={setNodeRef}
      className="relative z-0 data-[dragging=true]:z-10 data-[dragging=true]:opacity-80"
      style={{
        transform: CSS.Transform.toString(transform),
        transition: transition,
      }}
    >
      {row.getVisibleCells().map((cell) => (
        <TableCell key={cell.id}>
          {flexRender(cell.column.columnDef.cell, cell.getContext())}
        </TableCell>
      ))}
    </TableRow>
  )
}

export function DataTable({ tickets, loading, error }: DataTableProps) {
  const { props } = usePage<PageProps>()
  const [data, setData] = React.useState<z.infer<typeof ticketSchema>[]>(tickets)
  const [rowSelection, setRowSelection] = React.useState({})
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({})
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([])
  const [sorting, setSorting] = React.useState<SortingState>([])
  const [pagination, setPagination] = React.useState({
    pageIndex: 0,
    pageSize: 5,
  })
  const [filters, setFilters] = React.useState({
    status: props.filters?.status || '',
    priority: props.filters?.priority || '',
    category: props.filters?.category || '',
    search: props.filters?.search || ''
  })
  const sortableId = React.useId()
  const sensors = useSensors(
    useSensor(MouseSensor, {}),
    useSensor(TouchSensor, {}),
    useSensor(KeyboardSensor, {})
  )

  // Update data when tickets prop changes
  React.useEffect(() => {
    setData(tickets)
  }, [tickets])

  
  React.useEffect(() => {
    let filteredData = tickets;
    
   
   if (filters.status && filters.status !== 'All_value') {
      filteredData = filteredData.filter(ticket => ticket.status === filters.status);
    }
    
    
    if (filters.priority && filters.priority !== 'All_priority') {
      filteredData = filteredData.filter(ticket => ticket.priority === filters.priority);
    }
    
    // e
    if (filters.category && filters.category !== 'All_catagories') {
      filteredData = filteredData.filter(ticket => ticket.category === filters.category);
    }
    
   
    if (filters.search) {
      const searchTerm = filters.search.toLowerCase();
      filteredData = filteredData.filter(ticket => 
        ticket.ticket_number.toLowerCase().includes(searchTerm)
      );
    }
    
    setData(filteredData);
  }, [filters, tickets]);

  const dataIds = React.useMemo<UniqueIdentifier[]>(
    () => data?.map(({ id }) => id) || [],
    [data]
  )

  const table = useReactTable({
    data,
    columns,
    state: {
      sorting,
      columnVisibility,
      rowSelection,
      columnFilters,
      pagination,
    },
    getRowId: (row) => row.id.toString(),
    enableRowSelection: true,
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    onPaginationChange: setPagination,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
  })

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event
    if (active && over && active.id !== over.id) {
      setData((data) => {
        const oldIndex = dataIds.indexOf(active.id)
        const newIndex = dataIds.indexOf(over.id)
        return arrayMove(data, oldIndex, newIndex)
      })
    }
  }

  const clearFilters = () => {
    setFilters({
      status: '',
      priority: '',
      category: '',
      search: ''
    })
  }

  if (error) {
    return (
      <div className="px-4 lg:px-6">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error Loading Tickets</AlertTitle>
          <AlertDescription>
            {error}
          </AlertDescription>
        </Alert>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    )
  }

  return (
    <Tabs defaultValue="tickets" className="w-full flex-col justify-start gap-6">
      <div className="flex items-center justify-between px-4 lg:px-6">
        <Label htmlFor="view-selector" className="sr-only">
          View
        </Label>
        <Select defaultValue="tickets">
          <SelectTrigger
            className="flex w-fit @4xl/main:hidden"
            size="sm"
            id="view-selector"
          >
            <SelectValue placeholder="Select a view" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="tickets">Tickets</SelectItem>
            <SelectItem value="statistics">Statistics</SelectItem>
            <SelectItem value="resolvers">Resolvers</SelectItem>
          </SelectContent>
        </Select>
        <TabsList className="**:data-[slot=badge]:bg-muted-foreground/30 hidden **:data-[slot=badge]:size-5 **data-[slot=badge]:rounded-full **:data-[slot=badge]:px-1 @4xl/main:flex">
          <TabsTrigger value="tickets">
            Tickets <Badge variant="secondary">{data.length}</Badge>
          </TabsTrigger>
          <TabsTrigger value="statistics">
            Statistics
          </TabsTrigger>
          <TabsTrigger value="resolvers">
            Resolvers
          </TabsTrigger>
        </TabsList>
        <div className="flex items-center gap-2">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="sm">
                <IconLayoutColumns />
                <span className="hidden lg:inline">Customize Columns</span>
                <span className="lg:hidden">Columns</span>
                <IconChevronDown />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              {table
                .getAllColumns()
                .filter(
                  (column) =>
                    typeof column.accessorFn !== "undefined" &&
                    column.getCanHide()
                )
                .map((column) => {
                  return (
                    <DropdownMenuCheckboxItem
                      key={column.id}
                      className="capitalize"
                      checked={column.getIsVisible()}
                      onCheckedChange={(value) =>
                        column.toggleVisibility(!!value)
                      }
                    >
                      {column.id}
                    </DropdownMenuCheckboxItem>
                  )
                })}
            </DropdownMenuContent>
          </DropdownMenu>
          <Button 
            variant="outline" 
            size="sm"
            onClick={() => window.location.href = '/tickets/create'}
          >
            <IconPlus />
            <span className="hidden lg:inline">New Ticket</span>
          </Button>
        </div>
      </div>

      {/* Filter Controls */}
      <div className="px-4 lg:px-6">
        <div className="flex flex-wrap items-center gap-4 p-4 bg-muted/50 rounded-lg mb-4">
          <div className="flex flex-col gap-2">
            <Label htmlFor="status-filter" className="text-sm">Status</Label>
            <Select
              value={filters.status}
              onValueChange={(value) => setFilters({...filters, status: value})}
            >
              <SelectTrigger id="status-filter" className="w-[150px]">
                <SelectValue placeholder="All Statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="All_value">All Statuses</SelectItem>
                <SelectItem value="open">Open</SelectItem>
                <SelectItem value="assigned">Assigned</SelectItem>
                <SelectItem value="in_progress">In Progress</SelectItem>
                <SelectItem value="resolved">Resolved</SelectItem>
                <SelectItem value="closed">Closed</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="flex flex-col gap-2">
            <Label htmlFor="priority-filter" className="text-sm">Priority</Label>
            <Select
              value={filters.priority}
              onValueChange={(value) => setFilters({...filters, priority: value})}
            >
              <SelectTrigger id="priority-filter" className="w-[150px]">
                <SelectValue placeholder="All Priorities" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="All_priority">All Priorities</SelectItem>
                <SelectItem value="low">Low</SelectItem>
                <SelectItem value="medium">Medium</SelectItem>
                <SelectItem value="high">High</SelectItem>
                <SelectItem value="critical">Critical</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="flex flex-col gap-2">
            <Label htmlFor="category-filter" className="text-sm">Category</Label>
            <Select
              value={filters.category}
              onValueChange={(value) => setFilters({...filters, category: value})}
            >
              <SelectTrigger id="category-filter" className="w-[150px]">
                <SelectValue placeholder="All Categories" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="All_catagories">All Categories</SelectItem>
                <SelectItem value="technical">Technical</SelectItem>
                <SelectItem value="billing">Billing</SelectItem>
                <SelectItem value="general">General</SelectItem>
                <SelectItem value="support">Support</SelectItem>
                <SelectItem value="hardware">Hardware</SelectItem>
                <SelectItem value="software">Software</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="flex flex-col gap-2">
            <Label htmlFor="search-filter" className="text-sm">Search Ticket #</Label>
            <div className="relative">
              <IconSearch className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="search-filter"
                placeholder="Search by ticket number..."
                value={filters.search}
                onChange={(e) => setFilters({...filters, search: e.target.value})}
                className="w-[250px] pl-8"
              />
              {filters.search && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="absolute right-1 top-1 h-6 w-6"
                  onClick={() => setFilters({...filters, search: ''})}
                >
                  <IconX className="h-3 w-3" />
                </Button>
              )}
            </div>
          </div>

          <div className="flex flex-col gap-2">
            <Label className="text-sm opacity-0">Clear</Label>
            <Button
              variant="outline"
              onClick={clearFilters}
              className="whitespace-nowrap"
              disabled={!filters.status && !filters.priority && !filters.category && !filters.search}
            >
              <IconX className="mr-2 h-4 w-4" />
              Clear Filters
            </Button>
          </div>
        </div>
      </div>

      <TabsContent
        value="tickets"
        className="relative flex flex-col gap-4 overflow-auto px-4 lg:px-6"
      >
        <div className="overflow-hidden rounded-lg border">
          <DndContext
            collisionDetection={closestCenter}
            modifiers={[restrictToVerticalAxis]}
            onDragEnd={handleDragEnd}
            sensors={sensors}
            id={sortableId}
          >
            <Table>
              <TableHeader className="bg-muted sticky top-0 z-10">
                {table.getHeaderGroups().map((headerGroup) => (
                  <TableRow key={headerGroup.id}>
                    {headerGroup.headers.map((header) => {
                      return (
                        <TableHead key={header.id} colSpan={header.colSpan}>
                          {header.isPlaceholder
                            ? null
                            : flexRender(
                                header.column.columnDef.header,
                                header.getContext()
                              )}
                        </TableHead>
                      )
                    })}
                  </TableRow>
                ))}
              </TableHeader>
              <TableBody className="**:data-[slot=table-cell]:first:w-8">
                {table.getRowModel().rows?.length ? (
                  <SortableContext
                    items={dataIds}
                    strategy={verticalListSortingStrategy}
                  >
                    {table.getRowModel().rows.map((row) => (
                      <DraggableRow key={row.id} row={row} />
                    ))}
                  </SortableContext>
                ) : (
                  <TableRow>
                    <TableCell
                      colSpan={columns.length}
                      className="h-24 text-center"
                    >
                      {filters.status || filters.priority || filters.category || filters.search ? (
                        <div className="flex flex-col items-center gap-2">
                          <IconFilter className="h-8 w-8 text-muted-foreground" />
                          <p>No tickets match your filters</p>
                          <Button variant="outline" size="sm" onClick={clearFilters}>
                            Clear Filters
                          </Button>
                        </div>
                      ) : (
                        "No tickets found in your department."
                      )}
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </DndContext>
        </div>
        <div className="flex items-center justify-between px-4">
          <div className="text-muted-foreground hidden flex-1 text-sm lg:flex">
            {table.getFilteredSelectedRowModel().rows.length} of{" "}
            {table.getFilteredRowModel().rows.length} row(s) selected.
          </div>
          <div className="flex w-full items-center gap-8 lg:w-fit">
            <div className="hidden items-center gap-2 lg:flex">
              <Label htmlFor="rows-per-page" className="text-sm font-medium">
                Rows per page
              </Label>
              <Select
                value={`${table.getState().pagination.pageSize}`}
                onValueChange={(value) => {
                  table.setPageSize(Number(value))
                }}
              >
                <SelectTrigger size="sm" className="w-20" id="rows-per-page">
                  <SelectValue
                    placeholder={table.getState().pagination.pageSize}
                  />
                </SelectTrigger>
                <SelectContent side="top">
                  {[5, 10, 20, 30, 40, 50].map((pageSize) => (
                    <SelectItem key={pageSize} value={`${pageSize}`}>
                      {pageSize}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex w-fit items-center justify-center text-sm font-medium">
              Page {table.getState().pagination.pageIndex + 1} of{" "}
              {table.getPageCount()}
            </div>
            <div className="ml-auto flex items-center gap-2 lg:ml-0">
              <Button
                variant="outline"
                className="hidden h-8 w-8 p-0 lg:flex"
                onClick={() => table.setPageIndex(0)}
                disabled={!table.getCanPreviousPage()}
              >
                <span className="sr-only">Go to first page</span>
                <IconChevronsLeft />
              </Button>
              <Button
                variant="outline"
                className="size-8"
                size="icon"
                onClick={() => table.previousPage()}
                disabled={!table.getCanPreviousPage()}
              >
                <span className="sr-only">Go to previous page</span>
                <IconChevronLeft />
              </Button>
              <Button
                variant="outline"
                className="size-8"
                size="icon"
                onClick={() => table.nextPage()}
                disabled={!table.getCanNextPage()}
              >
                <span className="sr-only">Go to next page</span>
                <IconChevronRight />
              </Button>
              <Button
                variant="outline"
                className="hidden size-8 lg:flex"
                size="icon"
                onClick={() => table.setPageIndex(table.getPageCount() - 1)}
                disabled={!table.getCanNextPage()}
              >
                <span className="sr-only">Go to last page</span>
                <IconChevronsRight />
              </Button>
            </div>
            </div>
        </div>
      </TabsContent>
      <TabsContent
        value="statistics"
        className="flex flex-col px-4 lg:px-6"
      >
        <div className="aspect-video w-full flex-1 rounded-lg border border-dashed">
          <p className="text-center p-8 text-muted-foreground">Statistics view coming soon</p>
        </div>
      </TabsContent>
      <TabsContent value="resolvers" className="flex flex-col px-4 lg:px-6">
        <div className="aspect-video w-full flex-1 rounded-lg border border-dashed">
          <p className="text-center p-8 text-muted-foreground">Resolvers view coming soon</p>
        </div>
      </TabsContent>
    </Tabs>
  )
}

function TableCellViewer({ item }: { item: z.infer<typeof ticketSchema> }) {
  const isMobile = useIsMobile()
  const { props } = usePage<PageProps>()
  const [loading, setLoading] = useState(false)
  const [assignmentType, setAssignmentType] = useState<'myself' | 'resolvers' | ''>('')
  const [resolverType, setResolverType] = useState<'individual' | 'group' | ''>('')
  const [selectedResolvers, setSelectedResolvers] = useState<any[]>([])
  const [selectedDepartment, setSelectedDepartment] = useState<any>(null)
  const [forwardNotes, setForwardNotes] = useState('')
  const [dueDate, setDueDate] = useState(item.due_date ? new Date(item.due_date).toISOString().split('T')[0] : '')
  const [status, setStatus] = useState(item.status)
  const [availableResolvers, setAvailableResolvers] = useState<any[]>([])
  const [departments, setDepartments] = useState<any[]>([])
  const [showForwardSection, setShowForwardSection] = useState(false)
  const [searchResolverQuery, setSearchResolverQuery] = useState('')

  // Fetch available resolvers and departments on mount
  useEffect(() => {
    fetchAvailableResolvers()
    fetchDepartments()
  }, [])

  const fetchAvailableResolvers = async () => {
    try {
      const response = await fetch('/dept-admin/resolvers/available', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })
      if (response.ok) {
        const data = await response.json()
        setAvailableResolvers(data.resolvers || [])
      }
    } catch (error) {
      console.error('Error fetching resolvers:', error)
    }
  }

  const fetchDepartments = async () => {
    try {
      const response = await fetch('/departments/list', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })
      if (response.ok) {
        const data = await response.json()
        setDepartments(data.departments || [])
      }
    } catch (error) {
      console.error('Error fetching departments:', error)
    }
  }

  const handleAssignToMyself = async () => {
    setLoading(true)
    try {
      const response = await fetch(`/dept-admin/tickets/${item.id}/assign`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          action: 'assign_myself',
          resolver_id: props.auth.user.id,
          due_date: dueDate || null
        })
      })

      if (response.ok) {
        toast.success('Ticket assigned to you successfully!')
        window.location.reload()
      } else {
        const error = await response.json()
        toast.error(error.error || 'Failed to assign ticket')
      }
    } catch (error) {
      toast.error('Failed to assign ticket')
    } finally {
      setLoading(false)
    }
  }

  const handleAssignToIndividual = async () => {
    if (selectedResolvers.length !== 1) {
      toast.error('Please select exactly one resolver')
      return
    }

    setLoading(true)
    try {
      const response = await fetch(`/dept-admin/tickets/${item.id}/assign`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          action: 'assign_individual',
          resolver_id: selectedResolvers[0].id,
          due_date: dueDate || null
        })
      })

      if (response.ok) {
        toast.success('Ticket assigned successfully!')
        window.location.reload()
      } else {
        const error = await response.json()
        toast.error(error.error || 'Failed to assign ticket')
      }
    } catch (error) {
      toast.error('Failed to assign ticket')
    } finally {
      setLoading(false)
    }
  }

  const handleAssignToGroup = async () => {
    if (selectedResolvers.length < 2) {
      toast.error('Please select at least two resolvers for group assignment')
      return
    }

    setLoading(true)
    try {
      const response = await fetch(`/dept-admin/tickets/${item.id}/assign`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          action: 'assign_group',
          resolver_ids: selectedResolvers.map(r => r.id),
          due_date: dueDate || null
        })
      })

      if (response.ok) {
        toast.success('Ticket assigned to group successfully!')
        window.location.reload()
      } else {
        const error = await response.json()
        toast.error(error.error || 'Failed to assign ticket to group')
      }
    } catch (error) {
      toast.error('Failed to assign ticket to group')
    } finally {
      setLoading(false)
    }
  }

  const handleForwardTicket = async () => {
    if (!selectedDepartment || !forwardNotes.trim()) {
      toast.error('Please select a department and provide forwarding notes')
      return
    }

    setLoading(true)
    try {
      const response = await fetch(`/dept-admin/tickets/${item.id}/assign`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          action: 'forward',
          forward_to_department_id: selectedDepartment.id,
          forward_notes: forwardNotes,
          due_date: dueDate || null
        })
      })

      if (response.ok) {
        toast.success('Ticket forwarded successfully!')
        window.location.reload()
      } else {
        const error = await response.json()
        toast.error(error.error || 'Failed to forward ticket')
      }
    } catch (error) {
      toast.error('Failed to forward ticket')
    } finally {
      setLoading(false)
    }
  }

  const addResolverToSelection = (resolver: any) => {
    if (!selectedResolvers.find(r => r.id === resolver.id)) {
      setSelectedResolvers([...selectedResolvers, resolver])
    }
    setSearchResolverQuery('')
  }

  const removeResolverFromSelection = (resolverId: number) => {
    setSelectedResolvers(selectedResolvers.filter(r => r.id !== resolverId))
  }

  const filteredResolvers = availableResolvers.filter(resolver =>
    resolver.name.toLowerCase().includes(searchResolverQuery.toLowerCase()) ||
    resolver.email.toLowerCase().includes(searchResolverQuery.toLowerCase())
  )

  return (
    <Drawer direction={isMobile ? "bottom" : "right"}>
      <DrawerTrigger asChild>
        <Button variant="link" className="text-foreground w-fit px-0 text-left">
          {item.ticket_number}
        </Button>
      </DrawerTrigger>
      <DrawerContent className="max-w-2xl mx-auto">
        <DrawerHeader className="gap-1">
          <DrawerTitle>{item.subject}</DrawerTitle>
          <DrawerDescription>
            Ticket #{item.ticket_number} • Created {new Date(item.created_at).toLocaleDateString()}
          </DrawerDescription>
        </DrawerHeader>
        <div className="flex flex-col gap-6 overflow-y-auto px-4 text-sm max-h-[70vh]">
          {/* Ticket Details Section */}
          <form className="flex flex-col gap-4">
            <div className="flex flex-col gap-3">
              <Label htmlFor="subject">Subject</Label>
              <Input id="subject" defaultValue={item.subject} readOnly />
            </div>
            
            <div className="grid grid-cols-2 gap-4">
              <div className="flex flex-col gap-3">
                <Label htmlFor="category">Category</Label>
                <Badge variant="outline" className="text-muted-foreground px-1.5 py-2 h-10 w-full justify-start">
                  {item.category}
                </Badge>
              </div>
              <div className="flex flex-col gap-3">
                <Label htmlFor="priority">Priority</Label>
                <Badge 
                  variant={
                    item.priority.toLowerCase() === 'high' ? 'destructive' :
                    item.priority.toLowerCase() === 'medium' ? 'default' :
                    item.priority.toLowerCase() === 'low' ? 'secondary' : 'outline'
                  } 
                  className="px-1.5 py-2 h-10 w-full justify-start"
                >
                  {item.priority}
                </Badge>
              </div>
            </div>
            
            <div className="grid grid-cols-2 gap-4">
              <div className="flex flex-col gap-3">
                <Label htmlFor="status">Status</Label>
                <Select value={status} onValueChange={setStatus}>
                  <SelectTrigger id="status" className="w-full">
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="open">Open</SelectItem>
                    <SelectItem value="assigned">Assigned</SelectItem>
                    <SelectItem value="in_progress">In Progress</SelectItem>
                    <SelectItem value="resolved">Resolved</SelectItem>
                    <SelectItem value="closed">Closed</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="flex flex-col gap-3">
                <Label htmlFor="due_date">Due Date</Label>
                <Input 
                  id="due_date" 
                  type="date" 
                  value={dueDate}
                  onChange={(e) => setDueDate(e.target.value)}
                  min={new Date().toISOString().split('T')[0]}
                />
              </div>
            </div>
            
            <div className="flex flex-col gap-3">
              <Label htmlFor="description">Description</Label>
              <textarea 
                id="description" 
                defaultValue={item.description}
                className="w-full min-h-[100px] p-2 border rounded-md"
                readOnly
              />
            </div>
          </form>

          <Separator />

          {/* Assignment Section */}
          <div className="flex flex-col gap-4">
            <h3 className="text-base font-semibold">Assignment Options</h3>
            
            {/* Assignment Type Selection */}
            <div className="flex flex-col gap-3">
              <Label>Assign to</Label>
              <Select value={assignmentType} onValueChange={(value: 'myself' | 'resolvers' | '') => {
                setAssignmentType(value)
                setResolverType('')
                setSelectedResolvers([])
                setShowForwardSection(false)
              }}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select assignment option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="myself">Myself</SelectItem>
                  <SelectItem value="resolvers">Resolvers</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Myself Assignment */}
            {assignmentType === 'myself' && (
              <div className="flex flex-col gap-3 p-4 border rounded-lg bg-muted/50">
                <p className="text-sm text-muted-foreground">
                  Assign this ticket to yourself for resolution.
                </p>
                <Button 
                  onClick={handleAssignToMyself} 
                  disabled={loading}
                  className="w-full"
                >
                  {loading ? 'Assigning...' : 'Assign to Myself'}
                </Button>
              </div>
            )}

            {/* Resolvers Assignment */}
            {assignmentType === 'resolvers' && (
              <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3">
                  <Label>Assignment Type</Label>
                  <Select value={resolverType} onValueChange={(value: 'individual' | 'group' | '') => {
                    setResolverType(value)
                    setSelectedResolvers([])
                  }}>
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Select resolver type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="individual">Individual</SelectItem>
                      <SelectItem value="group">Group</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                {/* Individual Resolver Selection */}
                {resolverType === 'individual' && (
                  <div className="flex flex-col gap-3">
                    <Label>Select Resolver</Label>
                    <div className="relative">
                      <Input
                        placeholder="Search resolvers..."
                        value={searchResolverQuery}
                        onChange={(e) => setSearchResolverQuery(e.target.value)}
                        className="w-full"
                      />
                      {searchResolverQuery && filteredResolvers.length > 0 && (
                        <div className="absolute z-10 w-full mt-1 bg-background border rounded-md shadow-lg max-h-48 overflow-y-auto">
                          {filteredResolvers.map((resolver) => (
                            <div
                              key={resolver.id}
                              onClick={() => addResolverToSelection(resolver)}
                              className="p-2 hover:bg-muted cursor-pointer border-b last:border-b-0"
                            >
                              <div className="font-medium">{resolver.name}</div>
                              <div className="text-sm text-muted-foreground">{resolver.email}</div>
                              <div className="text-xs text-muted-foreground">ID: {resolver.id}</div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                    
                    {selectedResolvers.length > 0 && (
                      <div className="flex flex-col gap-2">
                        <Label>Selected Resolver</Label>
                        {selectedResolvers.map((resolver) => (
                          <div key={resolver.id} className="flex items-center justify-between p-2 border rounded">
                            <div>
                              <div className="font-medium">{resolver.name}</div>
                              <div className="text-sm text-muted-foreground">ID: {resolver.id}</div>
                            </div>
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={() => removeResolverFromSelection(resolver.id)}
                            >
                              <IconX className="h-4 w-4" />
                            </Button>
                          </div>
                        ))}
                      </div>
                    )}
                    
                    <Button 
                      onClick={handleAssignToIndividual} 
                      disabled={loading || selectedResolvers.length !== 1}
                      className="w-full"
                    >
                      {loading ? 'Assigning...' : 'Assign to Individual'}
                    </Button>
                  </div>
                )}

                {/* Group Resolver Selection */}
                {resolverType === 'group' && (
                  <div className="flex flex-col gap-3">
                    <Label>Select Resolvers (Minimum 2)</Label>
                    <div className="relative">
                      <Input
                        placeholder="Search resolvers..."
                        value={searchResolverQuery}
                        onChange={(e) => setSearchResolverQuery(e.target.value)}
                        className="w-full"
                      />
                      {searchResolverQuery && filteredResolvers.length > 0 && (
                        <div className="absolute z-10 w-full mt-1 bg-background border rounded-md shadow-lg max-h-48 overflow-y-auto">
                          {filteredResolvers.map((resolver) => (
                            <div
                              key={resolver.id}
                              onClick={() => addResolverToSelection(resolver)}
                              className="p-2 hover:bg-muted cursor-pointer border-b last:border-b-0"
                            >
                              <div className="font-medium">{resolver.name}</div>
                              <div className="text-sm text-muted-foreground">{resolver.email}</div>
                              <div className="text-xs text-muted-foreground">ID: {resolver.id}</div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                    
                    {selectedResolvers.length > 0 && (
                      <div className="flex flex-col gap-2">
                        <Label>Selected Resolvers ({selectedResolvers.length})</Label>
                        {selectedResolvers.map((resolver) => (
                          <div key={resolver.id} className="flex items-center justify-between p-2 border rounded">
                            <div>
                              <div className="font-medium">{resolver.name}</div>
                              <div className="text-sm text-muted-foreground">ID: {resolver.id}</div>
                            </div>
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={() => removeResolverFromSelection(resolver.id)}
                            >
                              <IconX className="h-4 w-4" />
                            </Button>
                          </div>
                        ))}
                      </div>
                    )}
                    
                    <Button 
                      onClick={handleAssignToGroup} 
                      disabled={loading || selectedResolvers.length < 2}
                      className="w-full"
                    >
                      {loading ? 'Assigning...' : `Assign to Group (${selectedResolvers.length} selected)`}
                    </Button>
                  </div>
                )}
              </div>
            )}

            {/* Forward Ticket Section */}
            <div className="flex flex-col gap-3">
              <Button
                variant="outline"
                onClick={() => setShowForwardSection(!showForwardSection)}
                className="w-full"
              >
                {showForwardSection ? 'Cancel Forward' : 'Forward to Another Department'}
              </Button>
              
              {showForwardSection && (
                <div className="flex flex-col gap-3 p-4 border rounded-lg bg-muted/50">
                  <div className="flex flex-col gap-3">
                    <Label>Target Department</Label>
                    <Select value={selectedDepartment?.id?.toString() || ''} onValueChange={(value) => {
                      const dept = departments.find(d => d.id.toString() === value)
                      setSelectedDepartment(dept)
                    }}>
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Select department" />
                      </SelectTrigger>
                      <SelectContent>
                        {departments.filter(d => d.id !== props.auth.user.department_id).map((dept) => (
                          <SelectItem key={dept.id} value={dept.id.toString()}>
                            {dept.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  
                  <div className="flex flex-col gap-3">
                    <Label>Forwarding Notes</Label>
                    <textarea
                      placeholder="Enter notes for the receiving department..."
                      value={forwardNotes}
                      onChange={(e) => setForwardNotes(e.target.value)}
                      className="w-full min-h-[80px] p-2 border rounded-md"
                    />
                  </div>
                  
                  <Button 
                    onClick={handleForwardTicket} 
                    disabled={loading || !selectedDepartment || !forwardNotes.trim()}
                    className="w-full"
                  >
                    {loading ? 'Forwarding...' : 'Forward Ticket'}
                  </Button>
                </div>
              )}
            </div>
          </div>
        </div>
        <DrawerFooter>
          <DrawerClose asChild>
            <Button variant="outline">Close</Button>
          </DrawerClose>
        </DrawerFooter>
      </DrawerContent>
    </Drawer>
  )
}