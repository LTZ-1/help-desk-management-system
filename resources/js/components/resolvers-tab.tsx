import * as React from "react";
import { useState, useEffect } from "react";
import {
  IconUser,
  IconMail,
  IconCalendar,
  IconCheck,
  IconLoader,
  IconChartBar,
} from "@tabler/icons-react";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { toast } from "sonner";

interface Resolver {
  id: number;
  name: string;
  email: string;
  department_id: number;
  is_active: boolean;
  joined_at: string;
  tickets_resolved: number;
  tickets_assigned: number;
  tickets_in_progress: number;
}

export function ResolversTab() {
  const [resolvers, setResolvers] = useState<Resolver[]>([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    total_resolvers: 0,
    active_resolvers: 0,
    total_resolved: 0,
    total_assigned: 0,
  });

  useEffect(() => {
    fetchResolvers();
  }, []);

  const fetchResolvers = async () => {
    setLoading(true);
    try {
      const response = await fetch("/department/resolvers");
      const data = await response.json();

      if (response.ok) {
        setResolvers(data.resolvers);
        calculateStats(data.resolvers);
      } else {
        toast.error("Failed to load resolvers");
      }
    } catch (error) {
      console.error("Error fetching resolvers:", error);
      toast.error("Error loading resolvers");
    } finally {
      setLoading(false);
    }
  };

  const calculateStats = (resolversList: Resolver[]) => {
    const active = resolversList.filter((r) => r.is_active).length;
    const resolved = resolversList.reduce((sum, r) => sum + r.tickets_resolved, 0);
    const assigned = resolversList.reduce((sum, r) => sum + r.tickets_assigned, 0);

    setStats({
      total_resolvers: resolversList.length,
      active_resolvers: active,
      total_resolved: resolved,
      total_assigned: assigned,
    });
  };

  const getInitials = (name: string) => {
    return name
      .split(" ")
      .map((part) => part[0])
      .join("")
      .toUpperCase()
      .slice(0, 2);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <IconLoader className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Statistics Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Resolvers</CardTitle>
            <IconUser className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.total_resolvers}</div>
            <p className="text-xs text-muted-foreground">
              {stats.active_resolvers} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Resolvers</CardTitle>
            <IconCheck className="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.active_resolvers}</div>
            <p className="text-xs text-muted-foreground">
              {((stats.active_resolvers / stats.total_resolvers) * 100 || 0).toFixed(0)}% of total
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Tickets Resolved</CardTitle>
            <IconCheck className="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.total_resolved}</div>
            <p className="text-xs text-muted-foreground">
              Avg {(stats.total_resolved / stats.total_resolvers || 0).toFixed(1)} per resolver
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Currently Assigned</CardTitle>
            <IconChartBar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.total_assigned}</div>
            <p className="text-xs text-muted-foreground">
              Tickets in progress
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Resolvers Table */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Resolver</TableHead>
              <TableHead>Contact</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Joined</TableHead>
              <TableHead className="text-center">Resolved</TableHead>
              <TableHead className="text-center">Assigned</TableHead>
              <TableHead className="text-center">In Progress</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {resolvers.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="h-24 text-center">
                  No resolvers found in your department.
                </TableCell>
              </TableRow>
            ) : (
              resolvers.map((resolver) => (
                <TableRow key={resolver.id}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <Avatar className="h-8 w-8">
                        <AvatarFallback>{getInitials(resolver.name)}</AvatarFallback>
                      </Avatar>
                      <div>
                        <p className="font-medium">{resolver.name}</p>
                        <p className="text-xs text-muted-foreground">ID: {resolver.id}</p>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <IconMail className="h-3 w-3 text-muted-foreground" />
                      <span className="text-sm">{resolver.email}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    {resolver.is_active ? (
                      <Badge variant="default" className="bg-green-500">
                        Active
                      </Badge>
                    ) : (
                      <Badge variant="outline">Inactive</Badge>
                    )}
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <IconCalendar className="h-3 w-3 text-muted-foreground" />
                      <span className="text-sm">{resolver.joined_at}</span>
                    </div>
                  </TableCell>
                  <TableCell className="text-center">
                    <Badge variant="secondary" className="bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                      {resolver.tickets_resolved}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-center">
                    <Badge variant="outline" className="bg-blue-50 dark:bg-blue-950">
                      {resolver.tickets_assigned}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-center">
                    <Badge variant="outline" className="bg-yellow-50 dark:bg-yellow-950">
                      {resolver.tickets_in_progress}
                    </Badge>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}