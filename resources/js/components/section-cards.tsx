// section-cards.tsx
"use client"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { AlertCircle, UsersIcon } from "lucide-react"

interface DepartmentStats {
  total_tickets_to_resolve: number
  assigned_tickets: number
  resolved_tickets: number
  overdue_tickets: number
  active_resolvers: number
  assigned_resolver_groups: number
  // Optional fields for other contexts
  total_tickets?: number
  open_tickets?: number
  in_progress_tickets?: number
  group_tickets?: number
  individual_tickets?: number
  total_users?: number
  active_users?: number
  inactive_users?: number
  total_departments?: number
  average_resolution_time?: number
}

interface SectionCardsProps {
  statistics: DepartmentStats
  loading: boolean
  error?: string
}

export function SectionCards({ statistics, loading, error }: SectionCardsProps) {
  if (error) {
    return (
      <div className="px-4 lg:px-6">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>
            Failed to load statistics: {error}
          </AlertDescription>
        </Alert>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="grid gap-4 md:grid-cols-2 md:gap-8 lg:grid-cols-4 px-4 lg:px-6">
        {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
          <Card key={i}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                <div className="h-4 bg-muted rounded w-24 animate-pulse"></div>
              </CardTitle>
              <div className="h-4 w-4 bg-muted rounded animate-pulse"></div>
            </CardHeader>
            <CardContent>
              <div className="h-8 bg-muted rounded w-16 animate-pulse"></div>
              <div className="text-xs text-muted-foreground mt-2">
                <div className="h-3 bg-muted rounded w-32 animate-pulse"></div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

 return (
    <div className="grid gap-4 md:grid-cols-2 md:gap-8 lg:grid-cols-4 px-4 lg:px-6">
      {/* Total Tickets to Resolve */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Tickets</CardTitle>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            className="h-4 w-4 text-muted-foreground"
          >
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
          </svg>
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{statistics.total_tickets_to_resolve || 0}</div>
          <p className="text-xs text-muted-foreground">
            Tickets to resolve in department
          </p>
        </CardContent>
      </Card>
      
      {/* Assigned Tickets */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Assigned Tickets</CardTitle>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            className="h-4 w-4 text-muted-foreground"
          >
            <circle cx="12" cy="12" r="10" />
            <path d="M12 8v4M12 16h.01" />
          </svg>
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{statistics.assigned_tickets || 0}</div>
          <p className="text-xs text-muted-foreground">
            Currently assigned to resolvers
          </p>
        </CardContent>
      </Card>
      
      {/* Resolved Tickets */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Resolved Tickets</CardTitle>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            className="h-4 w-4 text-muted-foreground"
          >
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <path d="m9 11 3 3L22 4" />
          </svg>
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{statistics.resolved_tickets || 0}</div>
          <p className="text-xs text-muted-foreground">
            Successfully completed
          </p>
        </CardContent>
      </Card>
      
      {/* Overdue Tickets */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Overdue Tickets</CardTitle>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            className="h-4 w-4 text-muted-foreground"
          >
            <circle cx="12" cy="12" r="10" />
            <path d="M12 6v6l4 2" />
          </svg>
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold text-destructive">{statistics.overdue_tickets || 0}</div>
          <p className="text-xs text-muted-foreground">
            Past due date
          </p>
        </CardContent>
      </Card>
      
      {/* Active Resolvers */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Active Resolvers</CardTitle>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            className="h-4 w-4 text-muted-foreground"
          >
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{statistics.active_resolvers || 0}</div>
          <p className="text-xs text-muted-foreground">
            Available team members
          </p>
        </CardContent>
      </Card>
      
      {/* Assigned Resolver Groups */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Resolver Groups</CardTitle>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            className="h-4 w-4 text-muted-foreground"
          >
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{statistics.assigned_resolver_groups || 0}</div>
          <p className="text-xs text-muted-foreground">
            Active resolver groups
          </p>
        </CardContent>
      </Card>
    </div>
  )
}

