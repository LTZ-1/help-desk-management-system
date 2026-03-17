// chart-area-interactive.tsx
"use client"
import * as React from "react"
import { Area, AreaChart, CartesianGrid, XAxis, YAxis, ResponsiveContainer, Legend } from "recharts"

import { useIsMobile } from "@/hooks/use-mobile"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from "@/components/ui/chart"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from "@/components/ui/alert"
import { AlertCircle } from "lucide-react"

export const description = "An interactive area chart"

interface ChartAreaInteractiveProps {
  chartData: any[]
  loading: boolean
  error?: string
  chartType?: 'tickets' | 'resolved_tickets' | 'user'
}

const chartConfig = {
  tickets: {
    label: "Tickets Created",
    color: "hsl(221, 83%, 53%)", // Blue
  },
  assigned_tickets: {
    label: "Tickets Assigned", 
    color: "hsl(142, 71%, 45%)", // Green
  },
  resolved_tickets: {
    label: "Tickets Resolved",
    color: "hsl(262, 83%, 58%)", // Purple
  },
} satisfies ChartConfig

export function ChartAreaInteractive({ chartData, loading, error, chartType = 'tickets' }: ChartAreaInteractiveProps) {
  const isMobile = useIsMobile()
  const [timeRange, setTimeRange] = React.useState("90d")
  const [filteredData, setFilteredData] = React.useState<any[]>([])
  const [selectedMetrics, setSelectedMetrics] = React.useState<string[]>(['tickets', 'assigned_tickets', 'resolved_tickets'])

  React.useEffect(() => {
    if (isMobile) {
      setTimeRange("7d")
    }
  }, [isMobile])

  React.useEffect(() => {
    if (chartData && chartData.length > 0) {
      const now = new Date();
      let daysToSubtract = 90;
      
      if (timeRange === "30d") {
        daysToSubtract = 30;
      } else if (timeRange === "7d") {
        daysToSubtract = 7;
      }
      
      const startDate = new Date(now);
      startDate.setDate(startDate.getDate() - daysToSubtract);
      
      const filtered = chartData.filter((item) => {
        const itemDate = new Date(item.date);
        return itemDate >= startDate && itemDate <= now;
      });
      
      setFilteredData(filtered);
    } else {
      setFilteredData([]);
    }
  }, [chartData, timeRange])

  const toggleMetric = (metric: string) => {
    setSelectedMetrics(prev => 
      prev.includes(metric) 
        ? prev.filter(m => m !== metric)
        : [...prev, metric]
    )
  }

  if (error) {
    return (
      <Card className="w-full">
        <CardHeader>
          <CardTitle>Ticket Analytics</CardTitle>
          <CardDescription>Department ticket trends over time</CardDescription>
        </CardHeader>
        <CardContent>
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
              Failed to load chart data: {error}
            </AlertDescription>
          </Alert>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card className="w-full">
      <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
        <div className="space-y-1">
          <CardTitle>Ticket Analytics</CardTitle>
          <CardDescription>Department ticket trends over time</CardDescription>
        </div>
        <div className="flex flex-col sm:flex-row gap-2">
          <Select value={timeRange} onValueChange={setTimeRange}>
            <SelectTrigger className="w-full sm:w-[180px]">
              <SelectValue placeholder="Select time range" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="7d">Last 7 days</SelectItem>
              <SelectItem value="30d">Last 30 days</SelectItem>
              <SelectItem value="90d">Last 3 months</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardHeader>
      <CardContent className="px-2 sm:px-6">
        {loading ? (
          <div className="flex h-[300px] items-center justify-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : filteredData.length === 0 ? (
          <div className="flex h-[300px] items-center justify-center text-muted-foreground">
            No data available for selected time range
          </div>
        ) : (
          <div className="w-full h-[300px]">
            <ChartContainer
              config={chartConfig}
              className="w-full h-full"
            >
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={filteredData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                  <defs>
                    <linearGradient id="gradient-tickets" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="hsl(221, 83%, 53%)" stopOpacity={0.8}/>
                      <stop offset="95%" stopColor="hsl(221, 83%, 53%)" stopOpacity={0.1}/>
                    </linearGradient>
                    <linearGradient id="gradient-assigned_tickets" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="hsl(142, 71%, 45%)" stopOpacity={0.8}/>
                      <stop offset="95%" stopColor="hsl(142, 71%, 45%)" stopOpacity={0.1}/>
                    </linearGradient>
                    <linearGradient id="gradient-resolved_tickets" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="hsl(262, 83%, 58%)" stopOpacity={0.8}/>
                      <stop offset="95%" stopColor="hsl(262, 83%, 58%)" stopOpacity={0.1}/>
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis
                    dataKey="date"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={32}
                    tickFormatter={(value) => {
                      const date = new Date(value)
                      return date.toLocaleDateString("en-US", {
                        month: "short",
                        day: "numeric",
                      })
                    }}
                    className="text-xs sm:text-sm"
                  />
                  <YAxis
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    tickCount={3}
                    className="text-xs sm:text-sm"
                  />
                  <ChartTooltip
                    content={
                      <ChartTooltipContent
                        labelFormatter={(value) => {
                          const date = new Date(value)
                          return date.toLocaleDateString("en-US", {
                            month: "short",
                            day: "numeric",
                            year: "numeric",
                          })
                        }}
                        indicator="line"
                        className="bg-background border border-border"
                      />
                    }
                  />
                  {selectedMetrics.includes('tickets') && (
                    <Area
                      dataKey="tickets"
                      type="monotone"
                      fill={`url(#gradient-tickets)`}
                      fillOpacity={0.6}
                      stroke="hsl(221, 83%, 53%)"
                      strokeWidth={2}
                    />
                  )}
                  {selectedMetrics.includes('assigned_tickets') && (
                    <Area
                      dataKey="assigned_tickets"
                      type="monotone"
                      fill={`url(#gradient-assigned_tickets)`}
                      fillOpacity={0.6}
                      stroke="hsl(142, 71%, 45%)"
                      strokeWidth={2}
                    />
                  )}
                  {selectedMetrics.includes('resolved_tickets') && (
                    <Area
                      dataKey="resolved_tickets"
                      type="monotone"
                      fill={`url(#gradient-resolved_tickets)`}
                      fillOpacity={0.6}
                      stroke="hsl(262, 83%, 58%)"
                      strokeWidth={2}
                    />
                  )}
                </AreaChart>
              </ResponsiveContainer>
            </ChartContainer>
            <div className="flex flex-wrap gap-2 mt-4">
              <div className="flex items-center space-x-2">
                <div className="w-3 h-3 rounded" style={{ backgroundColor: "hsl(221, 83%, 53%)" }}></div>
                <label className="text-sm font-medium">Tickets Created</label>
                <input
                  type="checkbox"
                  checked={selectedMetrics.includes('tickets')}
                  onChange={() => toggleMetric('tickets')}
                  className="rounded"
                />
              </div>
              <div className="flex items-center space-x-2">
                <div className="w-3 h-3 rounded" style={{ backgroundColor: "hsl(142, 71%, 45%)" }}></div>
                <label className="text-sm font-medium">Tickets Assigned</label>
                <input
                  type="checkbox"
                  checked={selectedMetrics.includes('assigned_tickets')}
                  onChange={() => toggleMetric('assigned_tickets')}
                  className="rounded"
                />
              </div>
              <div className="flex items-center space-x-2">
                <div className="w-3 h-3 rounded" style={{ backgroundColor: "hsl(262, 83%, 58%)" }}></div>
                <label className="text-sm font-medium">Tickets Resolved</label>
                <input
                  type="checkbox"
                  checked={selectedMetrics.includes('resolved_tickets')}
                  onChange={() => toggleMetric('resolved_tickets')}
                  className="rounded"
                />
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  )
}
