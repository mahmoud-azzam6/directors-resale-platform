"use client";

import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { BriefcaseBusiness, Plus, Search, ShieldCheck } from "lucide-react";
import { authApi } from "@/lib/api/auth";
import { administrationApi } from "@/lib/api/administration";
import type { ManagedPosition } from "@/types/administration";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { EmptyState } from "@/components/ui/empty-state";
import { ForbiddenState } from "@/components/ui/forbidden-state";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { PageHeader } from "@/components/layout/page-header";
import { Skeleton } from "@/components/ui/skeleton";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

const empty = { name: "", code: "", organization_id: "" };

export function PositionAdministrationPage() {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState("");
    const [selected, setSelected] = useState<ManagedPosition | null>(null);
    const [form, setForm] = useState(empty);
    const [permissionIds, setPermissionIds] = useState<number[]>([]);
    const context = useQuery({
        queryKey: ["auth-context"],
        queryFn: authApi.context,
    });
    const canView = context.data?.permissions.includes("positions.view");
    const positions = useQuery({
        queryKey: ["admin-positions"],
        queryFn: administrationApi.positions,
        enabled: canView,
    });
    const organizations = useQuery({
        queryKey: ["position-admin-organizations"],
        queryFn: administrationApi.positionOrganizations,
        enabled: canView,
    });
    const catalog = useQuery({
        queryKey: ["permission-catalog"],
        queryFn: administrationApi.catalog,
        enabled: context.data?.permissions.includes("permissions.view"),
    });
    const assigned = useQuery({
        queryKey: ["position-permissions", selected?.id],
        queryFn: () => administrationApi.positionPermissions(selected!.id),
        enabled:
            Boolean(selected) &&
            context.data?.permissions.includes("permissions.view"),
    });
    const save = useMutation({
        mutationFn: () =>
            selected
                ? administrationApi.updatePosition(selected.id, {
                      name: form.name,
                      code: form.code,
                      status: "active",
                  })
                : administrationApi.createPosition({
                      name: form.name,
                      code: form.code,
                      organization_id: Number(form.organization_id),
                      status: "active",
                  }),
        onSuccess: async () => {
            setSelected(null);
            setForm(empty);
            setPermissionIds([]);
            await queryClient.invalidateQueries({
                queryKey: ["admin-positions"],
            });
        },
    });
    const permissions = useMutation({
        mutationFn: () =>
            administrationApi.assignPermissions(selected!.id, permissionIds),
        onSuccess: async (savedPermissions) => {
            setPermissionIds(savedPermissions.map((permission) => permission.id));
            await queryClient.invalidateQueries({
                queryKey: ["position-permissions", selected?.id],
            });
            await queryClient.refetchQueries({
                queryKey: ["position-permissions", selected?.id],
                type: "active",
            });
        },
    });
    const deactivate = useMutation({
        mutationFn: (id: number) => administrationApi.deactivatePosition(id),
        onSuccess: async () => {
            setSelected(null);
            setForm(empty);
            await queryClient.invalidateQueries({
                queryKey: ["admin-positions"],
            });
        },
    });
    const organizationMap = useMemo(
        () =>
            new Map((organizations.data ?? []).map((item) => [item.id, item])),
        [organizations.data],
    );
    const visible = (positions.data ?? []).filter((position) =>
        `${position.name} ${position.code} ${organizationMap.get(position.organization_id)?.name ?? ""}`
            .toLowerCase()
            .includes(search.toLowerCase()),
    );
    const delegable = (catalog.data ?? []).filter((permission) =>
        context.data?.permissions.includes(permission.code),
    );

    function edit(position: ManagedPosition) {
        permissions.reset();
        setSelected(position);
        setForm({
            name: position.name,
            code: position.code,
            organization_id: String(position.organization_id),
        });
        setPermissionIds([]);
    }
    useEffect(() => {
        setPermissionIds(
            assigned.data?.map((permission) => permission.id) ?? [],
        );
    }, [assigned.data, selected?.id]);
    if (context.isLoading)
        return (
            <main className="page-shell">
                <Skeleton className="h-40" />
            </main>
        );
    if (!canView)
        return (
            <main className="page-shell">
                <ForbiddenState />
            </main>
        );

    return (
        <main className="page-shell fade-up">
            <PageHeader
                eyebrow="People and access"
                title="Positions"
                description="Manage Organization-owned Positions and safely delegate only capabilities you currently hold."
            />
            <div className="mt-8 grid gap-8 xl:grid-cols-[1fr_380px]">
                <section>
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h2 className="text-lg font-semibold text-ink">
                                Scoped Positions
                            </h2>
                            <p className="mt-1 text-sm text-muted">
                                Position ownership remains fixed to its
                                Organization.
                            </p>
                        </div>
                        <div className="relative sm:w-72">
                            <Search
                                className="absolute left-3 top-3.5 text-muted"
                                size={16}
                            />
                            <Input
                                className="pl-9"
                                placeholder="Search Position or Organization"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>
                    </div>
                    {positions.isLoading ? (
                        <div className="mt-5">
                            <Skeleton className="h-40" />
                        </div>
                    ) : positions.error ? (
                        <div className="mt-5">
                            <Alert>Positions could not be loaded.</Alert>
                        </div>
                    ) : visible.length === 0 ? (
                        <div className="mt-5">
                            <EmptyState
                                title="No active Positions"
                                description="No Positions match the current search and authorized scope."
                            />
                        </div>
                    ) : (
                        <Card className="mt-5 overflow-hidden">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <tr>
                                            <TableHead>Position</TableHead>
                                            <TableHead>Organization</TableHead>
                                            <TableHead>Status</TableHead>
                                        </tr>
                                    </TableHeader>
                                    <TableBody>
                                        {visible.map((position) => (
                                            <TableRow
                                                key={position.id}
                                                className="cursor-pointer"
                                                onClick={() => edit(position)}
                                            >
                                                <TableCell>
                                                    <p className="font-semibold">
                                                        {position.name}
                                                    </p>
                                                    <p className="mt-1 font-mono text-xs text-muted">
                                                        {position.code}
                                                    </p>
                                                </TableCell>
                                                <TableCell>
                                                    {organizationMap.get(
                                                        position.organization_id,
                                                    )?.name ??
                                                        `Organization #${position.organization_id}`}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="success">
                                                        {position.status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    )}
                </section>
                <section className="xl:border-l xl:border-line xl:pl-7">
                    <div className="flex items-center gap-3">
                        <span className="grid h-10 w-10 place-items-center rounded-md bg-brand/10 text-brand">
                            {selected ? (
                                <BriefcaseBusiness size={18} />
                            ) : (
                                <Plus size={18} />
                            )}
                        </span>
                        <div>
                            <h2 className="font-semibold text-ink">
                                {selected
                                    ? "Position details"
                                    : "Create Position"}
                            </h2>
                            <p className="text-xs text-muted">
                                {selected
                                    ? organizationMap.get(
                                          selected.organization_id,
                                      )?.name
                                    : "Select the owning Organization"}
                            </p>
                        </div>
                    </div>
                    {(save.error || permissions.error || deactivate.error) && (
                        <div className="mt-4">
                            <Alert>
                                {save.error instanceof Error
                                    ? save.error.message
                                    : permissions.error instanceof Error
                                      ? permissions.error.message
                                      : "The Position action failed."}
                            </Alert>
                        </div>
                    )}
                    <form
                        className="mt-6 space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            save.mutate();
                        }}
                    >
                        <div>
                            <Label>Name</Label>
                            <Input
                                required
                                value={form.name}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        name: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div>
                            <Label>Code</Label>
                            <Input
                                required
                                value={form.code}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        code: event.target.value,
                                    })
                                }
                            />
                        </div>
                        {selected ? (
                            <div>
                                <Label>Organization</Label>
                                <div className="rounded-md border border-line bg-surface-muted px-3 py-3 text-sm font-semibold text-ink">
                                    {organizationMap.get(selected.organization_id)?.name ??
                                        `Organization #${selected.organization_id}`}
                                </div>
                                <p className="mt-2 text-xs text-muted">
                                    Position ownership cannot be changed through editing.
                                </p>
                            </div>
                        ) : (
                            <div>
                                <Label>Organization</Label>
                                <select
                                    required
                                    className="mt-2 h-11 w-full rounded-md border border-line bg-surface px-3 text-sm"
                                    value={form.organization_id}
                                    onChange={(event) =>
                                        setForm({
                                            ...form,
                                            organization_id: event.target.value,
                                        })
                                    }
                                >
                                    <option value="">Select Organization</option>
                                    {organizations.data?.map((organization) => (
                                        <option key={organization.id} value={organization.id}>
                                            {organization.name} ({organization.organization_type})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                        {(selected
                            ? context.data?.permissions.includes(
                                  "positions.update",
                              )
                            : context.data?.permissions.includes(
                                  "positions.create",
                              )) && (
                            <Button
                                className="w-full"
                                disabled={save.isPending}
                            >
                                {selected ? "Save Position" : "Create Position"}
                            </Button>
                        )}
                    </form>
                    {selected &&
                        context.data?.permissions.includes(
                            "permissions.view",
                        ) && (
                            <div className="mt-7 border-t border-line pt-6">
                                <h3 className="flex items-center gap-2 text-sm font-semibold">
                                    <ShieldCheck
                                        size={16}
                                        className="text-brand"
                                    />{" "}
                                    Delegable capabilities
                                </h3>
                                <p className="mt-1 text-xs leading-5 text-muted">
                                    The backend rejects capabilities outside
                                    your own effective Permission set.
                                </p>
                                {assigned.isLoading || catalog.isLoading ? (
                                    <Skeleton className="mt-4 h-28" />
                                ) : (
                                    <div className="mt-4 max-h-52 space-y-1 overflow-y-auto">
                                        {delegable.map((permission) => (
                                            <label
                                                key={permission.id}
                                                className="flex items-center gap-3 px-2 py-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={permissionIds.includes(
                                                        permission.id,
                                                    )}
                                                    onChange={(event) => {
                                                        permissions.reset();
                                                        setPermissionIds(
                                                            event.target.checked
                                                                ? [...permissionIds, permission.id]
                                                                : permissionIds.filter((id) => id !== permission.id),
                                                        );
                                                    }}
                                                />
                                                <span>{permission.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                )}
                                {permissions.isSuccess && (
                                    <div className="mt-4">
                                        <Alert tone="success">
                                            Permission assignments saved and refreshed.
                                        </Alert>
                                    </div>
                                )}
                                {context.data.permissions.includes(
                                    "permissions.assign",
                                ) && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="mt-4 w-full"
                                        disabled={permissions.isPending}
                                        onClick={() => permissions.mutate()}
                                    >
                                        {permissions.isPending
                                            ? "Saving Permissions..."
                                            : "Save Permissions"}
                                    </Button>
                                )}
                            </div>
                        )}
                    {selected &&
                        context.data?.permissions.includes(
                            "positions.archive",
                        ) && (
                            <Button
                                type="button"
                                variant="danger"
                                className="mt-5 w-full"
                                onClick={() =>
                                    window.confirm(
                                        `Deactivate ${selected.name}? The Position row and User relationships remain.`,
                                    ) && deactivate.mutate(selected.id)
                                }
                            >
                                Deactivate Position
                            </Button>
                        )}
                    {selected && (
                        <Button
                            type="button"
                            variant="ghost"
                            className="mt-2 w-full"
                            onClick={() => {
                                setSelected(null);
                                setForm(empty);
                                setPermissionIds([]);
                            }}
                        >
                            Create another Position
                        </Button>
                    )}
                </section>
            </div>
        </main>
    );
}
