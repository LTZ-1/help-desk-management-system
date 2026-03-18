// resolver-details-drawer.tsx - 
"use client"
import * as React from "react"
import { useState } from "react"
import { Drawer, DrawerContent, DrawerHeader, DrawerTitle } from "@/components/ui/drawer"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import { User, Mail, Phone, Building, Calendar, CheckCircle, XCircle, UserCheck, UserX } from "lucide-react"
import { toast } from "sonner"

interface Resolver {
  id: number
  name: string
  email: string
  branch?: string
  phone?: string
  is_active: boolean
  last_login?: string
  resolved_tickets_count: number
  department_id: number
  created_at?: string
}

interface ResolverDetailsDrawerProps {
  resolver: Resolver | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onUpdate: () => void
}

export function ResolverDetailsDrawer({ resolver, open, onOpenChange, onUpdate }: ResolverDetailsDrawerProps) {
  const [loading, setLoading] = useState(false)

  const toggleResolverStatus = async (isActive: boolean) => {
    if (!resolver) return
    
    setLoading(true)
    try {
      const response = await fetch(`/department/resolvers/${resolver.id}/toggle-status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ is_active: isActive })
      })
      
      if (!response.ok) {
        throw new Error('Failed to update resolver status')
      }
      
      toast.success(`Resolver ${isActive ? 'activated' : 'deactivated'} successfully`)
      onUpdate()
      onOpenChange(false)
    } catch (error) {
      console.error('Error updating resolver status:', error)
      toast.error('Failed to update resolver status')
    } finally {
      setLoading(false)
    }
  }

  if (!resolver) return null

  return (
    <Drawer open={open} onOpenChange={onOpenChange}>
      <DrawerContent className="max-h-[80vh]">
        <DrawerHeader className="text-left">
          <DrawerTitle className="flex items-center gap-2">
            <User className="h-5 w-5" />
            Resolver Details
          </DrawerTitle>
        </DrawerHeader>
        
        <div className="p-4 pb-8 space-y-6">
          {/* Status Badge */}
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold">{resolver.name}</h3>
              <p className="text-sm text-muted-foreground">ID: {resolver.id}</p>
            </div>
            <Badge variant={resolver.is_active ? "default" : "secondary"} className="text-sm">
              {resolver.is_active ? "Active" : "Inactive"}
            </Badge>
          </div>

          <Separator />

          {/* Contact Information */}
          <div className="space-y-4">
            <h4 className="text-sm font-medium text-muted-foreground">Contact Information</h4>
            <div className="grid gap-3">
              <div className="flex items-center gap-3">
                <Mail className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">Email</p>
                  <p className="text-sm">{resolver.email}</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Phone className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">Phone</p>
                  <p className="text-sm">{resolver.phone || "Not provided"}</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Building className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">Branch</p>
                  <p className="text-sm">{resolver.branch || "Not assigned"}</p>
                </div>
              </div>
            </div>
          </div>

          <Separator />

          {/* Activity Information */}
          <div className="space-y-4">
            <h4 className="text-sm font-medium text-muted-foreground">Activity Information</h4>
            <div className="grid gap-3">
              <div className="flex items-center gap-3">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">Last Login</p>
                  <p className="text-sm">
                    {resolver.last_login 
                      ? new Date(resolver.last_login).toLocaleDateString() + ' ' + new Date(resolver.last_login).toLocaleTimeString()
                      : "Never"
                    }
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <CheckCircle className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">Resolved Tickets</p>
                  <p className="text-sm">{resolver.resolved_tickets_count} tickets</p>
                </div>
              </div>
              {resolver.created_at && (
                <div className="flex items-center gap-3">
                  <Calendar className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-sm font-medium">Member Since</p>
                    <p className="text-sm">
                      {new Date(resolver.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>

          <Separator />

          {/* Actions */}
          <div className="space-y-3">
            <h4 className="text-sm font-medium text-muted-foreground">Actions</h4>
            <div className="flex gap-2">
              {resolver.is_active ? (
                <Button
                  variant="destructive"
                  onClick={() => toggleResolverStatus(false)}
                  disabled={loading}
                  className="flex items-center gap-2"
                >
                  <UserX className="h-4 w-4" />
                  {loading ? "Deactivating..." : "Deactivate Account"}
                </Button>
              ) : (
                <Button
                  variant="default"
                  onClick={() => toggleResolverStatus(true)}
                  disabled={loading}
                  className="flex items-center gap-2"
                >
                  <UserCheck className="h-4 w-4" />
                  {loading ? "Activating..." : "Activate Account"}
                </Button>
              )}
            </div>
          </div>
        </div>
      </DrawerContent>
    </Drawer>
  )
}
