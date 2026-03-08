// resolvers-tab.tsx - Fixed version without statistics cards
"use client"
import * as React from "react"
import { useState, useEffect } from "react"
import { usePage } from "@inertiajs/react"
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
  SortingState,
  useReactTable,
  VisibilityState,
} from "@tanstack/react-table"
import { ArrowUpDown, ChevronDown, MoreHorizontal, User, UserX, UserCheck, Search, X, Filter, Phone, Building } from "lucide-react"
import { toast } from "sonner"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from "@/components/ui/alert"
import { AlertCircle } from "lucide-react"
import { ResolverDetailsDrawer } from "@/components/resolver-details-drawer"

interface Resolver {
  id: number;
  name: string;
  email: string;
  branch?: string;
  phone?: string;
  is_active: boolean;
  is_resolver: boolean;
  last_login?: string;
  resolved_tickets_count: number;
  department_id: number;
}

interface PageProps {
  auth: {
    user: any
  }
  [key: string]: any
}

export function ResolversTab() {
  const { props } = usePage<PageProps>()
  const [data, setData] = useState<Resolver[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [filters, setFilters] = useState({
    search: ''
  })
  const [selectedResolver, setSelectedResolver] = useState<Resolver | null>(null)
  const [drawerOpen, setDrawerOpen] = useState(false)

  const columns: ColumnDef<Resolver>[] = [
    {
      id: "select",
      header: ({ table }) => (
        <Checkbox
          checked={
            table.getIsAllPageRowsSelected() ||
            (table.getIsSomePageRowsSelected() && "indeterminate")
          }
          onCheckedChange={(value: 'indeterminate' | boolean | undefined) => table.toggleAllPageRowsSelected(!!value)}
          aria-label="Select all"
        />
      ),
      cell: ({ row }) => (
        <Checkbox
          checked={row.getIsSelected()}
          onCheckedChange={(value: 'indeterminate' | boolean | undefined) => row.toggleSelected(!!value)}
          aria-label="Select row"
        />
      ),
      enableSorting: false,
      enableHiding: false,
    },
    {
      accessorKey: "id",
      header: ({ column }) => (
        <Button
          variant="ghost"
          size="sm"
          className="-ml-3 h-8 data-[state=open]:bg-accent/50"
          onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        >
          <span>ID</span>
          <ArrowUpDown className="ml-2 h-4 w-4" />
        </Button>
      ),
      cell: ({ row }) => (
        <div className="font-medium">
          {row.getValue("id")}
        </div>
      ),
    },
    {
      accessorKey: "name",
      header: ({ column }) => (
        <Button
          variant="ghost"
          size="sm"
          className="-ml-3 h-8 data-[state=open]:bg-accent/50"
          onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        >
          <span>Name</span>
          <ArrowUpDown className="ml-2 h-4 w-4" />
        </Button>
      ),
      cell: ({ row }) => (
        <div className="flex items-center gap-2">
          <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center">
            <User className="h-4 w-4" />
          </div>
          <div className="font-medium">
            {row.getValue("name")}
          </div>
        </div>
      ),
    },
    {
      accessorKey: "email",
      header: "Email",
      cell: ({ row }) => (
        <div className="text-sm">
          {row.getValue("email")}
        </div>
      ),
    },
    {
      accessorKey: "branch",
      header: "Branch",
      cell: ({ row }) => (
        <div className="flex items-center gap-2">
          <Building className="h-3 w-3 text-muted-foreground" />
          <span className="text-sm">{row.getValue("branch") || "N/A"}</span>
        </div>
      ),
    },
    {
      accessorKey: "phone",
      header: "Phone",
      cell: ({ row }) => (
        <div className="flex items-center gap-2">
          <Phone className="h-3 w-3 text-muted-foreground" />
          <span className="text-sm">{row.getValue("phone") || "N/A"}</span>
        </div>
      ),
    },
    {
      accessorKey: "is_active",
      header: "Status",
      cell: ({ row }) => (
        <Badge variant={row.getValue("is_active") ? "default" : "secondary"}>
          {row.getValue("is_active") ? "Active" : "Inactive"}
        </Badge>
      ),
    },
    {
      accessorKey: "last_login",
      header: "Last Login",
      cell: ({ row }) => {
        const lastLogin = row.getValue("last_login")
        return (
          <div className="text-sm">
            {lastLogin ? new Date(lastLogin as string).toLocaleDateString() : "Never"}
          </div>
        )
      },
    },
    {
      accessorKey: "resolved_tickets_count",
      header: "Resolved Tickets",
      cell: ({ row }) => (
        <div className="text-center">
          <Badge variant="secondary">
            {row.getValue("resolved_tickets_count")}
          </Badge>
        </div>
      ),
    },
    {
      id: "actions",
      cell: ({ row }) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="h-8 w-8 p-0">
              <span className="sr-only">Open menu</span>
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => {
              setSelectedResolver(row.original)
              setDrawerOpen(true)
            }}>
              View Details
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            {row.getValue("is_active") ? (
              <DropdownMenuItem 
                className="text-red-600"
                onClick={() => toggleResolverStatus(row.original.id, false)}
              >
                <UserX className="mr-2 h-4 w-4" />
                Suspend
              </DropdownMenuItem>
            ) : (
              <DropdownMenuItem 
                className="text-green-600"
                onClick={() => toggleResolverStatus(row.original.id, true)}
              >
                <UserCheck className="mr-2 h-4 w-4" />
                Activate
              </DropdownMenuItem>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ]

  useEffect(() => {
    fetchResolvers()
  }, [])

  const fetchResolvers = async () => {
    setLoading(true)
    setError(null)
    try {
      // Use correct endpoint from Laravel routes: /dept-admin/resolvers
      const response = await fetch('/dept-admin/resolvers', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })
      
      if (!response.ok) {
        throw new Error('Failed to fetch resolvers')
      }
      
      const result = await response.json()
      
      // The backend already filters by department and is_resolver=true
      // Map the data structure from ResolverService
      const resolvers = (result.resolvers || []).map((resolver: any) => ({
        id: resolver.id,
        name: resolver.name,
        email: resolver.email,
        branch: resolver.branch,
        phone: resolver.phone,
        is_active: resolver.is_active,
        is_resolver: true, // All resolvers from this endpoint are resolvers
        last_login: resolver.last_login,
        resolved_tickets_count: resolver.statistics?.tickets_resolved || 0,
        department_id: resolver.department_id || props.auth.user?.department_id
      }))
      
      setData(resolvers)
    } catch (error) {
      console.error('Error fetching resolvers:', error)
      setError('Error loading resolvers')
      toast.error('Error loading resolvers')
    } finally {
      setLoading(false)
    }
  }

  const toggleResolverStatus = async (resolverId: number, isActive: boolean) => {
    try {
      // Use correct endpoint from Laravel routes: /dept-admin/resolvers/{resolverId}/status
      const response = await fetch(`/dept-admin/resolvers/${resolverId}/status`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ 
          status: isActive ? 'activate' : 'suspend'
        })
      })
      
      if (!response.ok) {
        throw new Error('Failed to update resolver status')
      }
      
      toast.success(`Resolver ${isActive ? 'activated' : 'suspended'} successfully`)
      fetchResolvers() // Refresh data
    } catch (error) {
      console.error('Error updating resolver status:', error)
      toast.error('Failed to update resolver status')
    }
  }

  const table = useReactTable({
    data,
    columns,
    state: {
      sorting,
      columnVisibility,
      rowSelection,
      columnFilters,
    },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    onRowSelectionChange: setRowSelection,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
  })

  const clearFilters = () => {
    setFilters({
      search: ''
    })
  }

  if (error) {
    return (
      <div className="px-4 lg:px-6">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error Loading Resolvers</AlertTitle>
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
    <div className="w-full space-y-4">
      {/* Filters */}
      <div className="flex flex-col lg:flex-row gap-4 p-4 bg-muted/50 rounded-lg">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div className="flex flex-col sm:flex-row gap-2">
            <Label htmlFor="search-filter" className="text-sm font-medium">Search</Label>
            <div className="relative flex-1">
              <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="search-filter"
                placeholder="Search resolvers..."
                value={filters.search}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFilters({...filters, search: e.target.value})}
                className="w-full pl-8"
              />
              {filters.search && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="absolute right-1 top-1 h-6 w-6"
                  onClick={() => setFilters({...filters, search: ''})}
                >
                  <X className="h-3 w-3" />
                </Button>
              )}
            </div>
          </div>
        </div>
        <div className="flex justify-end mt-4 sm:mt-0">
          <Button
            variant="outline"
            onClick={clearFilters}
            className="w-full sm:w-auto px-4 py-2"
            disabled={!filters.search}
          >
            <X className="mr-2 h-4 w-4" />
            Clear Filters
          </Button>
        </div>
      </div>

      {/* Table */}
      <div className="rounded-md border">
        <div className="overflow-x-auto">
          <Table>
            <TableHeader className="bg-muted sticky top-0 z-10">
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id}>
                  {headerGroup.headers.map((header) => (
                    <TableHead key={header.id} className="whitespace-nowrap px-2 py-3">
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody>
              {table.getRowModel().rows?.length ? (
                table.getRowModel().rows.map((row) => (
                  <TableRow
                    key={row.id}
                    data-state={row.getIsSelected() && "selected"}
                  >
                    {row.getVisibleCells().map((cell) => (
                      <TableCell key={cell.id}>
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                      </TableCell>
                    ))}
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={columns.length} className="h-24 text-center">
                    {filters.search ? (
                      <div className="flex flex-col items-center gap-2">
                        <Filter className="h-8 w-8 text-muted-foreground" />
                        <p>No resolvers match your filters</p>
                        <Button variant="outline" size="sm" onClick={clearFilters}>
                          Clear Filters
                        </Button>
                      </div>
                    ) : (
                      "No resolvers found in your department."
                    )}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
      </div>

      {/* Pagination */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-4 px-4">
        <div className="text-sm text-muted-foreground">
          {table.getFilteredSelectedRowModel().rows.length > 0 && (
            <span>
              {table.getFilteredSelectedRowModel().rows.length} of{" "}
              {table.getFilteredRowModel().rows.length} row(s) selected.
            </span>
          )}
        </div>
        <div className="flex flex-col sm:flex-row items-center gap-2">
          <div className="flex items-center gap-2">
            <Label htmlFor="rows-per-page" className="text-sm font-medium">
              Rows per page
            </Label>
            <Select
              value={`${table.getState().pagination.pageSize}`}
              onValueChange={(value: string) => {
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
          <div className="flex items-center justify-center text-sm font-medium">
            Page {table.getState().pagination.pageIndex + 1} of{" "}
            {table.getPageCount()}
          </div>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              className="hidden h-8 w-8 p-0 sm:flex"
              onClick={() => table.setPageIndex(0)}
              disabled={!table.getCanPreviousPage()}
            >
              <span className="sr-only">Go to first page</span>
              <ChevronDown className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              className="h-8 w-8 p-0"
              onClick={() => table.previousPage()}
              disabled={!table.getCanPreviousPage()}
            >
              <span className="sr-only">Go to previous page</span>
              <ChevronDown className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              className="h-8 w-8 p-0"
              onClick={() => table.nextPage()}
              disabled={!table.getCanNextPage()}
            >
              <span className="sr-only">Go to next page</span>
              <ChevronDown className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              className="hidden h-8 w-8 p-0 sm:flex"
              onClick={() => table.setPageIndex(table.getPageCount() - 1)}
              disabled={!table.getCanNextPage()}
            >
              <span className="sr-only">Go to last page</span>
              <ChevronDown className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
      
      {/* Resolver Details Drawer */}
      <ResolverDetailsDrawer 
        resolver={selectedResolver}
        open={drawerOpen}
        onOpenChange={setDrawerOpen}
        onUpdate={fetchResolvers}
      />
    </div>
  )
}
