"use client";

import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, UserPlus, Users } from "lucide-react";
import { authApi } from "@/lib/api/auth";
import { administrationApi } from "@/lib/api/administration";
import type { ManagedUser } from "@/types/administration";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
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

const empty = {
    full_name: "",
    email: "",
    phone: "",
    organization_id: "",
    position_id: "",
};

export function UserAdministrationPage() {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState("");
    const [selected, setSelected] = useState<ManagedUser | null>(null);
    const [form, setForm] = useState(empty);
    const context = useQuery({
        queryKey: ["auth-context"],
        queryFn: authApi.context,
    });
    const canView = context.data?.permissions.includes("users.view");
    const users = useQuery({
        queryKey: ["admin-users"],
        queryFn: administrationApi.users,
        enabled: canView,
    });
    const organizations = useQuery({
        queryKey: ["user-admin-organizations"],
        queryFn: administrationApi.userOrganizations,
        enabled: canView,
    });
    const positions = useQuery({
        queryKey: ["admin-positions"],
        queryFn: administrationApi.positions,
        enabled: context.data?.permissions.includes("positions.view"),
    });
    const save = useMutation({
        mutationFn: () =>
            selected
                ? administrationApi.updateUser(selected.id, {
                      full_name: form.full_name,
                      email: form.email,
                      phone: form.phone || null,
                      position_id: form.position_id
                          ? Number(form.position_id)
                          : null,
                      status: "active",
                  })
                : administrationApi.createUser({
                      full_name: form.full_name,
                      email: form.email,
                      phone: form.phone || null,
                      organization_id: Number(form.organization_id),
                      position_id: form.position_id
                          ? Number(form.position_id)
                          : null,
                      status: "active",
                  }),
        onSuccess: async () => {
            setSelected(null);
            setForm(empty);
            await queryClient.invalidateQueries({ queryKey: ["admin-users"] });
        },
    });
    const deactivate = useMutation({
        mutationFn: (id: number) => administrationApi.deactivateUser(id),
        onSuccess: async () => {
            setSelected(null);
            setForm(empty);
            await queryClient.invalidateQueries({ queryKey: ["admin-users"] });
        },
    });

    const organizationMap = useMemo(
        () =>
            new Map((organizations.data ?? []).map((item) => [item.id, item])),
        [organizations.data],
    );
    const positionMap = useMemo(
        () => new Map((positions.data ?? []).map((item) => [item.id, item])),
        [positions.data],
    );
    const visible = (users.data ?? []).filter((user) =>
        `${user.full_name} ${user.email} ${organizationMap.get(user.organization_id)?.name ?? ""}`
            .toLowerCase()
            .includes(search.toLowerCase()),
    );
    const targetOrganizationId =
        selected?.organization_id ?? Number(form.organization_id || 0);
    const eligiblePositions = (positions.data ?? []).filter(
        (position) => position.organization_id === targetOrganizationId,
    );
    const canSave = selected
        ? context.data?.permissions.includes("users.update")
        : context.data?.permissions.includes("users.create");
    const selectedIsInactive = selected?.status === "inactive";

    function edit(user: ManagedUser) {
        setSelected(user);
        setForm({
            full_name: user.full_name,
            email: user.email,
            phone: user.phone ?? "",
            organization_id: String(user.organization_id),
            position_id: user.position_id ? String(user.position_id) : "",
        });
    }

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
                title="Users"
                description="Administer people within your authorized Organization scope without changing their Organization ownership."
            />
            <div className="mt-8 grid gap-8 xl:grid-cols-[1fr_360px]">
                <section>
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h2 className="text-lg font-semibold text-ink">
                                Scoped directory
                            </h2>
                            <p className="mt-1 text-sm text-muted">
                                {organizations.data?.length ?? 0} Organizations
                                in administrative scope
                            </p>
                        </div>
                        <div className="relative sm:w-72">
                            <Search
                                className="absolute left-3 top-3.5 text-muted"
                                size={16}
                            />
                            <Input
                                className="pl-9"
                                placeholder="Search people or Organization"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>
                    </div>
                    {users.isLoading ? (
                        <div className="mt-5 space-y-3">
                            <Skeleton className="h-16" />
                            <Skeleton className="h-16" />
                        </div>
                    ) : users.error ? (
                        <div className="mt-5">
                            <Alert>User directory could not be loaded.</Alert>
                        </div>
                    ) : visible.length === 0 ? (
                        <div className="mt-5">
                            <EmptyState
                                title="No Users"
                                description="No Users match the current search and authorized scope."
                            />
                        </div>
                    ) : (
                        <Card className="mt-5 overflow-hidden">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <tr>
                                            <TableHead>User</TableHead>
                                            <TableHead>Organization</TableHead>
                                            <TableHead>Position</TableHead>
                                            <TableHead>Status</TableHead>
                                        </tr>
                                    </TableHeader>
                                    <TableBody>
                                        {visible.map((user) => (
                                            <TableRow
                                                key={user.id}
                                                className="cursor-pointer"
                                                onClick={() => edit(user)}
                                            >
                                                <TableCell>
                                                    <p className="font-semibold">
                                                        {user.full_name}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted">
                                                        {user.email}
                                                    </p>
                                                </TableCell>
                                                <TableCell>
                                                    {organizationMap.get(
                                                        user.organization_id,
                                                    )?.name ??
                                                        `Organization #${user.organization_id}`}
                                                </TableCell>
                                                <TableCell>
                                                    {user.position_id
                                                        ? (positionMap.get(
                                                              user.position_id,
                                                          )?.name ??
                                                          `Position #${user.position_id}`)
                                                        : "Unassigned"}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            user.status === "active"
                                                                ? "success"
                                                                : "muted"
                                                        }
                                                    >
                                                        {user.status}
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
                <section className="border-l-0 border-line xl:border-l xl:pl-7">
                    <div className="flex items-center gap-3">
                        <span className="grid h-10 w-10 place-items-center rounded-md bg-brand/10 text-brand">
                            {selected ? (
                                <Users size={18} />
                            ) : (
                                <UserPlus size={18} />
                            )}
                        </span>
                        <div>
                            <h2 className="font-semibold text-ink">
                                {selected ? "User details" : "Create User"}
                            </h2>
                            <p className="text-xs text-muted">
                                {selected
                                    ? organizationMap.get(
                                          selected.organization_id,
                                      )?.name
                                    : "Choose the target Organization carefully"}
                            </p>
                        </div>
                    </div>
                    {(save.error || deactivate.error) && (
                        <div className="mt-4">
                            <Alert>
                                {save.error instanceof Error
                                    ? save.error.message
                                    : "The User action failed."}
                            </Alert>
                        </div>
                    )}
                    {selectedIsInactive && (
                        <div className="mt-4">
                            <Alert>
                                This inactive identity is visible for administration. Editing,
                                reactivation, and credential activation are not supported by the
                                current lifecycle.
                            </Alert>
                        </div>
                    )}
                    <form
                        className="mt-6 space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            if (!selectedIsInactive) save.mutate();
                        }}
                    >
                        <div>
                            <Label>Full name</Label>
                            <Input
                                disabled={selectedIsInactive}
                                required
                                value={form.full_name}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        full_name: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div>
                            <Label>Email</Label>
                            <Input
                                disabled={selectedIsInactive}
                                required
                                type="email"
                                value={form.email}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        email: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div>
                            <Label>Phone</Label>
                            <Input
                                disabled={selectedIsInactive}
                                value={form.phone}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        phone: event.target.value,
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
                                    Organization transfer requires a separate future transfer workflow.
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
                                            position_id: "",
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
                        <div>
                            <Label>Position</Label>
                            <select
                                disabled={selectedIsInactive}
                                className="mt-2 h-11 w-full rounded-md border border-line bg-surface px-3 text-sm"
                                value={form.position_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        position_id: event.target.value,
                                    })
                                }
                            >
                                <option value="">Unassigned</option>
                                {eligiblePositions.map((position) => (
                                    <option
                                        key={position.id}
                                        value={position.id}
                                    >
                                        {position.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {canSave && !selectedIsInactive && (
                            <Button
                                className="w-full"
                                disabled={save.isPending}
                            >
                                {save.isPending
                                    ? "Saving..."
                                    : selected
                                      ? "Save supported fields"
                                      : "Create User"}
                            </Button>
                        )}
                        {selected &&
                            !selectedIsInactive &&
                            context.data?.permissions.includes(
                                "users.archive",
                            ) && (
                                <Button
                                    type="button"
                                    variant="danger"
                                    className="w-full"
                                    disabled={deactivate.isPending}
                                    onClick={() =>
                                        window.confirm(
                                            `Deactivate ${selected.full_name}? The identity record will remain.`,
                                        ) && deactivate.mutate(selected.id)
                                    }
                                >
                                    Deactivate User
                                </Button>
                            )}
                        {selected && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="w-full"
                                onClick={() => {
                                    setSelected(null);
                                    setForm(empty);
                                }}
                            >
                                Create another User
                            </Button>
                        )}
                    </form>
                </section>
            </div>
        </main>
    );
}
