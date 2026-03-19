// resolver-dashboard.tsx
"use client"
import * as React from "react"
import { useState, useEffect } from "react"
import { usePage, router } from "@inertiajs/react"
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { ChartAreaInteractive } from "@/components/chart-area-interactive"
import { SectionCards } from "@/components/section-cards"
import { ResolverMyTicketsTab } from "../components/resolver-my-tickets-tab"
import { toast } from "sonner"

interface User {
  id: number
  name: string
  email: string
  department_id: number
  is_admin: boolean
  is_resolver: boolean
  is_none: boolean
  branch?: string
}

interface PageProps {
  auth: {
    user: User
  }
  resolverData?: {
    statistics: any
    chartData: any[]
    tickets: any[]
  }
  [key: string]: any
}

export default function ResolverDashboard() {
  const { props } = usePage<PageProps>()
  console.log('ResolverDashboard rendering with props:', props)
  
  const user = props.auth.user
  
  const [loading, setLoading] = useState(!props.resolverData)
  const [error, setError] = useState<string | null>(null)
  
  // Resolver-specific statistics with required admin properties for SectionCards compatibility
  const [statistics, setStatistics] = useState(props.resolverData?.statistics || {
    total_tickets_to_resolve: 0,
    assigned_tickets: 0,
    resolved_tickets: 0,
    overdue_tickets: 0,
    active_resolvers: 0,
    assigned_resolver_groups: 0,
    // Resolver-specific
    total_tickets_assigned: 0,
    in_progress_tickets: 0,
    group_tickets: 0,
    individual_tickets: 0
  })
  
  const [chartData, setChartData] = useState(props.resolverData?.chartData || [])
  const [tickets, setTickets] = useState(props.resolverData?.tickets || [])

  useEffect(() => {
    if (!props.resolverData) {
      fetchResolverData()
    }
  }, [])

  // Fetch resolver data using Inertia
  const fetchResolverData = async () => {
    try {
      setLoading(true)
      setError(null)
      
      // Use Inertia's router to reload with resolver data
      router.reload({
        only: ['resolverData'],
        onSuccess: (page: any) => {
          const resolverData = page.props.resolverData
          if (resolverData) {
            setStatistics({
              ...resolverData.statistics,
              total_tickets_to_resolve: resolverData.statistics.total_tickets_assigned || 0,
              active_resolvers: 1, // Current resolver
              assigned_resolver_groups: resolverData.statistics.group_tickets || 0
            })
            setChartData(resolverData.chartData || [])
            setTickets(resolverData.tickets || [])
          }
        },
        onError: (errors: any) => {
          setError('Failed to load resolver data')
        }
      })
      
    } catch (error) {
      console.error('Error fetching resolver data:', error)
      setError('Failed to load resolver data. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  // Refresh all resolver data
  const refreshResolverData = () => {
    fetchResolverData()
  }

  return (
    <div className="space-y-4 p-4 md:p-8 pt-6">
      {/* Error */}
      {error && (
        <div className="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 p-4 rounded-lg">
        <h3 className="font-semibold mb-2">Error Loading Data</h3>
        <p className="text-sm">{error}</p>
        <Button
          variant="outline"
          size="sm"
          onClick={() => refreshResolverData()}
          className="mt-2"
        >
          Retry
        </Button>
      </div>
      )}

      {/* Header */}
      <div className="flex items-center justify-between space-y-2">
        <div>
          <h2 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Resolver Dashboard
          </h2>
          <p className="text-gray-600 dark:text-gray-400">
            View and manage your assigned tickets
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => refreshResolverData()}
            disabled={loading}
          >
            Refresh
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <SectionCards statistics={statistics} loading={loading} error={error || undefined} role="resolver" />

      {/* Chart Area - Full width like section cards */}
      <div className="w-full overflow-x-auto">
        <div className="min-w-full">
          <ChartAreaInteractive 
            chartData={chartData} 
            loading={loading} 
            error={error || undefined}
            chartType="resolved_tickets"
          />
        </div>
      </div>

      {/* Data Table Tabs - Full width like section cards */}
      <div className="w-full overflow-x-auto">
        <div className="min-w-full">
          <Tabs defaultValue="my-tickets" className="space-y-4">
            <TabsList>
              <TabsTrigger value="my-tickets">My Tickets</TabsTrigger>
            </TabsList>
            
            <TabsContent value="my-tickets" className="space-y-4">
              <Card className="bg-white dark:bg-gray-800">
                <CardHeader>
                  <CardTitle>My Assigned Tickets</CardTitle>
                  <CardDescription>
                    Tickets assigned to you for resolution
                  </CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                  <div className="min-w-full">
                    <ResolverMyTicketsTab />
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </div>
    </div>
  )
}
