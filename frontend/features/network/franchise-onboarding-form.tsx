'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { CheckCircle2, ChevronDown, Plus, ShieldCheck } from 'lucide-react';
import Link from 'next/link';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import type { AuthContext } from '@/types/auth';
import type { OnboardingResult } from '@/types/network';
import { networkApi } from '@/lib/api/network';
import { ApiClientError } from '@/lib/api/client';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const schema = z.object({
  franchiseName: z.string().trim().min(1, 'Franchise name is required.'),
  franchiseCode: z.string().trim().min(1, 'Franchise code is required.'),
  administratorName: z.string().trim().min(1, 'Administrator name is required.'),
  administratorEmail: z.string().trim().email('Enter a valid administrator email.'),
  administratorPhone: z.string().trim().optional(),
  positionName: z.string().trim().min(1, 'Position name is required.'),
  positionCode: z.string().trim().min(1, 'Position code is required.'),
  permissions: z.array(z.coerce.number()).min(1, 'Select at least one administrator Permission.'),
});

type FormValues = z.infer<typeof schema>;

export function FranchiseOnboardingForm({ context }: { context: AuthContext }) {
  const queryClient = useQueryClient();
  const [result, setResult] = useState<OnboardingResult | null>(null);
  const permissions = useQuery({ queryKey: ['permissions'], queryFn: networkApi.permissions });
  const { register, handleSubmit, formState: { errors }, reset } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { permissions: [] },
  });
  const mutation = useMutation({
    mutationFn: networkApi.onboard,
    onSuccess: async (data) => {
      setResult(data);
      reset({ permissions: [] });
      await queryClient.invalidateQueries({ queryKey: ['franchises'] });
    },
  });

  function submit(values: FormValues) {
    setResult(null);
    mutation.mutate({
      franchise: {
        name: values.franchiseName,
        code: values.franchiseCode,
        parent_organization_id: context.organization!.id,
        status: 'active',
      },
      position: { name: values.positionName, code: values.positionCode },
      administrator: {
        full_name: values.administratorName,
        email: values.administratorEmail,
        phone: values.administratorPhone || null,
      },
      permissions: values.permissions,
    });
  }

  const apiError = mutation.error instanceof ApiClientError ? mutation.error : null;

  return <section className="border-t border-line pt-7">
    <div className="flex items-start gap-3"><div className="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-brand/10 text-brand"><Plus size={19} /></div><div><h2 className="text-lg font-semibold text-ink">Onboard a Franchise</h2><p className="mt-1 text-sm leading-6 text-muted">Create the Franchise, its initial Position, and credential-less administrator in one atomic workflow.</p></div></div>
    {mutation.error && <div className="mt-5"><Alert>{apiError?.message ?? 'Franchise onboarding could not be completed. No partial records were kept.'}</Alert></div>}
    {result && <div className="mt-5"><Alert tone="success"><div><p className="font-semibold">{result.franchise.name} is onboarded.</p><p className="mt-1 text-xs leading-5">{result.administrator.email} is assigned to {result.position.name}. Credential setup and account activation are still required through the supported administrative lifecycle.</p><Link href={`/admin/franchises/${result.franchise.id}`} className="mt-3 inline-flex font-semibold text-brand hover:underline">View Franchise</Link></div></Alert></div>}
    {!result && <form className="mt-6 space-y-7" onSubmit={handleSubmit(submit)} noValidate>
      <fieldset className="grid gap-4 md:grid-cols-2"><legend className="sr-only">Franchise identity</legend><div><Label htmlFor="franchiseName">Franchise name</Label><Input id="franchiseName" {...register('franchiseName')} />{errors.franchiseName && <p className="mt-2 text-xs text-danger">{errors.franchiseName.message}</p>}</div><div><Label htmlFor="franchiseCode">Franchise code</Label><Input id="franchiseCode" {...register('franchiseCode')} />{errors.franchiseCode && <p className="mt-2 text-xs text-danger">{errors.franchiseCode.message}</p>}</div></fieldset>
      <div className="rounded-md border border-line bg-surface-muted/55 px-4 py-3 text-sm"><span className="text-muted">Parent Organization</span><span className="ml-2 font-semibold text-ink">{context.organization?.name}</span><Badge variant="success" className="ml-3">Active on creation</Badge></div>
      <fieldset className="grid gap-4 md:grid-cols-2"><legend className="mb-4 flex items-center gap-2 text-sm font-semibold text-ink"><ShieldCheck size={17} className="text-brand" /> Initial administrator Position</legend><div><Label htmlFor="positionName">Position name</Label><Input id="positionName" {...register('positionName')} />{errors.positionName && <p className="mt-2 text-xs text-danger">{errors.positionName.message}</p>}</div><div><Label htmlFor="positionCode">Position code</Label><Input id="positionCode" {...register('positionCode')} />{errors.positionCode && <p className="mt-2 text-xs text-danger">{errors.positionCode.message}</p>}</div></fieldset>
      <fieldset><legend className="text-sm font-semibold text-ink">Approved capabilities</legend><p className="mt-1 text-xs leading-5 text-muted">Select from the System-controlled Permission catalog. The Position name does not grant authority.</p>{permissions.isLoading ? <p className="mt-4 text-sm text-muted">Loading Permission catalog...</p> : permissions.error ? <div className="mt-4"><Alert>Permission catalog could not be loaded.</Alert></div> : <div className="mt-4 grid max-h-56 gap-2 overflow-y-auto rounded-md border border-line bg-surface p-3 sm:grid-cols-2">{permissions.data?.map((permission) => <label key={permission.id} className="flex cursor-pointer items-start gap-3 rounded-sm px-2 py-2 text-sm hover:bg-surface-muted"><Checkbox value={permission.id} {...register('permissions')} /><span><span className="block font-medium text-ink">{permission.name}</span><span className="block text-xs text-muted">{permission.code}</span></span></label>)}</div>}{errors.permissions && <p className="mt-2 text-xs text-danger">{errors.permissions.message}</p>}</fieldset>
      <fieldset className="grid gap-4 md:grid-cols-2"><legend className="mb-4 text-sm font-semibold text-ink">Initial administrator</legend><div><Label htmlFor="administratorName">Full name</Label><Input id="administratorName" {...register('administratorName')} />{errors.administratorName && <p className="mt-2 text-xs text-danger">{errors.administratorName.message}</p>}</div><div><Label htmlFor="administratorEmail">Email</Label><Input id="administratorEmail" type="email" {...register('administratorEmail')} />{errors.administratorEmail && <p className="mt-2 text-xs text-danger">{errors.administratorEmail.message}</p>}</div><div className="md:col-span-2"><Label htmlFor="administratorPhone">Phone <span className="font-normal text-muted">(optional)</span></Label><Input id="administratorPhone" {...register('administratorPhone')} /></div></fieldset>
      <div className="flex flex-wrap items-center justify-between gap-4 border-t border-line pt-5"><p className="max-w-lg text-xs leading-5 text-muted">No password is generated or exposed. The administrator remains inactive until credential setup and activation are completed outside AF002.</p><Button type="submit" disabled={mutation.isPending || permissions.isLoading} className="gap-2">{mutation.isPending ? 'Provisioning...' : <>Complete onboarding <ChevronDown className="rotate-[-90deg]" size={16} /></>}</Button></div>
    </form>}
  </section>;
}
