'use client';

import Link from 'next/link';
import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ApiClientError } from '@/lib/api/client';
import { ownershipApi } from '@/lib/api/ownership';
import type { Owner, OwnerInput, OwnershipParty } from '@/types/ownership';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emptyOwner: OwnerInput = { party_type: 'individual', display_name: '', mobile: '', email: '' };
const messageFor = (error: unknown, fallback: string) => error instanceof ApiClientError ? error.message : fallback;

export function OwnershipStepPage({ id }: { id: string }) {
  const queryClient = useQueryClient();
  const [ownerForm, setOwnerForm] = useState<OwnerInput>(emptyOwner);
  const [editingOwnerId, setEditingOwnerId] = useState<number | null>(null);
  const [selectedOwnerId, setSelectedOwnerId] = useState<number | null>(null);
  const [draftParties, setDraftParties] = useState<Array<{ owner_id: number; share_percentage: string }>>([]);
  const [newPartyOwnerId, setNewPartyOwnerId] = useState<number | null>(null);
  const [newPartyShare, setNewPartyShare] = useState('');
  const [actingPartyId, setActingPartyId] = useState<number | null>(null);
  const [actingBasis, setActingBasis] = useState('');
  const [actingNotes, setActingNotes] = useState('');
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const owners = useQuery({ queryKey: ['owners'], queryFn: ownershipApi.owners });
  const current = useQuery({ queryKey: ['property-ownership-current', id], queryFn: () => ownershipApi.currentOwnership(id), retry: false });
  const history = useQuery({ queryKey: ['property-ownership-history', id], queryFn: () => ownershipApi.ownershipHistory(id) });
  const selectedOwner = useQuery({ queryKey: ['owner', selectedOwnerId], queryFn: () => ownershipApi.owner(selectedOwnerId!), enabled: selectedOwnerId !== null });

  const currentOwnership = current.data;
  const currentMissing = current.error instanceof ApiClientError && current.error.status === 404;
  const parties = useQuery({ queryKey: ['ownership-parties', currentOwnership?.id], queryFn: () => ownershipApi.parties(currentOwnership!.id), enabled: Boolean(currentOwnership) });
  const actingOwner = useQuery({ queryKey: ['acting-owner', currentOwnership?.id], queryFn: () => ownershipApi.actingOwner(currentOwnership!.id), enabled: Boolean(currentOwnership), retry: false });
  const actingHistory = useQuery({ queryKey: ['acting-owner-history', currentOwnership?.id], queryFn: () => ownershipApi.actingOwnerHistory(currentOwnership!.id), enabled: Boolean(currentOwnership) });

  const invalidateOwnership = async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['property-ownership-current', id] }),
      queryClient.invalidateQueries({ queryKey: ['property-ownership-history', id] }),
      queryClient.invalidateQueries({ queryKey: ['ownership-parties'] }),
      queryClient.invalidateQueries({ queryKey: ['acting-owner'] }),
      queryClient.invalidateQueries({ queryKey: ['acting-owner-history'] }),
    ]);
  };

  const saveOwner = useMutation({
    mutationFn: () => editingOwnerId === null ? ownershipApi.createOwner(ownerForm) : ownershipApi.updateOwner(editingOwnerId, ownerForm),
    onSuccess: async (owner) => {
      await queryClient.invalidateQueries({ queryKey: ['owners'] });
      queryClient.setQueryData(['owner', owner.id], owner);
      setOwnerForm(emptyOwner);
      setEditingOwnerId(null);
      setSuccessMessage('تم حفظ المالك بنجاح.');
    },
  });

  const ownerLifecycle = useMutation({
    mutationFn: ({ owner, activate }: { owner: Owner; activate: boolean }) => activate ? ownershipApi.reactivateOwner(owner.id) : ownershipApi.deactivateOwner(owner.id),
    onSuccess: async () => { await queryClient.invalidateQueries({ queryKey: ['owners'] }); setSuccessMessage('تم تحديث حالة المالك.'); },
  });

  const createOwnership = useMutation({
    mutationFn: () => ownershipApi.createOwnership(id, { parties: draftParties }),
    onSuccess: async () => { setDraftParties([]); await invalidateOwnership(); setSuccessMessage('تم تسجيل الملكية الحالية.'); },
  });

  const closeOwnership = useMutation({
    mutationFn: () => ownershipApi.closeOwnership(currentOwnership!.id),
    onSuccess: async () => { await invalidateOwnership(); setSuccessMessage('تم إغلاق الملكية الحالية.'); },
  });

  const addParty = useMutation({
    mutationFn: () => ownershipApi.addParty(currentOwnership!.id, newPartyOwnerId!, newPartyShare),
    onSuccess: async () => { setNewPartyOwnerId(null); setNewPartyShare(''); await invalidateOwnership(); setSuccessMessage('تمت إضافة طرف الملكية.'); },
  });

  const updateParty = useMutation({
    mutationFn: (party: OwnershipParty) => ownershipApi.updatePartyShare(currentOwnership!.id, party.id, party.share_percentage),
    onSuccess: async () => { await invalidateOwnership(); setSuccessMessage('تم تحديث نسبة الملكية.'); },
  });

  const removeParty = useMutation({
    mutationFn: (partyId: number) => ownershipApi.removeParty(currentOwnership!.id, partyId),
    onSuccess: async () => { await invalidateOwnership(); setSuccessMessage('تمت إزالة طرف الملكية.'); },
  });

  const saveActingOwner = useMutation({
    mutationFn: () => {
      const data = { ownership_party_id: actingPartyId!, basis_source: actingBasis, notes: actingNotes || null };
      return actingOwner.data ? ownershipApi.changeActingOwner(currentOwnership!.id, data) : ownershipApi.designateActingOwner(currentOwnership!.id, data);
    },
    onSuccess: async () => { setActingBasis(''); setActingNotes(''); await invalidateOwnership(); setSuccessMessage('تم حفظ تعيين المالك المتصرف.'); },
  });

  const clearActingOwner = useMutation({ mutationFn: () => ownershipApi.clearActingOwner(currentOwnership!.id), onSuccess: async () => { await invalidateOwnership(); setSuccessMessage('تم إلغاء تعيين المالك المتصرف.'); } });

  const activeOwners = useMemo(() => owners.data?.filter((owner) => owner.status === 'active') ?? [], [owners.data]);
  const draftOwnerIds = new Set(draftParties.map((party) => party.owner_id));
  const partyOwner = (party: OwnershipParty) => owners.data?.find((owner) => owner.id === party.owner_id);
  const accessError = [owners.error, history.error, current.error].find((error) => error instanceof ApiClientError && error.status !== 404) as ApiClientError | undefined;

  if (owners.isLoading || history.isLoading || current.isLoading) {
    return <main className="page-shell"><p className="text-sm text-muted">Loading Owner and Ownership data...</p></main>;
  }

  if (accessError) {
    return <main className="page-shell"><Alert>{accessError.status === 403 ? 'Your current backend permissions do not allow private Owner or Ownership access for this Property.' : accessError.message}</Alert></main>;
  }

  return (
    <main className="page-shell fade-up" dir="rtl">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="eyebrow">إعداد الوحدة · 2 من 3</p>
          <h1 className="mt-2 text-3xl font-semibold text-ink">المالك والملكية</h1>
          <p className="mt-2 text-sm text-muted">Owner and Ownership remain private BF013 records. They are never stored in the Property Profile.</p>
        </div>
        <Button asChild variant="outline"><Link href={`/admin/properties/${id}/setup/property-data`}>العودة إلى بيانات الوحدة</Link></Button>
        <Button asChild variant="outline"><Link href={`/admin/properties/${id}/setup/review`}>{"\u0645\u0631\u0627\u062c\u0639\u0629 \u0627\u0644\u0625\u0639\u062f\u0627\u062f"}</Link></Button>
      </div>

      {successMessage && <div className="mt-6"><Alert tone="success">{successMessage}</Alert></div>}

      <Card className="mt-8">
        <CardHeader><h2 className="font-semibold text-ink">المالك / Owner</h2></CardHeader>
        <CardContent>
          {saveOwner.error && <Alert>{messageFor(saveOwner.error, 'Owner could not be saved.')}</Alert>}
          {ownerLifecycle.error && <div className="mt-4"><Alert>{messageFor(ownerLifecycle.error, 'Owner lifecycle could not be updated.')}</Alert></div>}
          <form className="mt-5 grid gap-4 md:grid-cols-2" onSubmit={(event) => { event.preventDefault(); saveOwner.mutate(); }}>
            <div><Label htmlFor="owner-name">اسم المالك</Label><Input id="owner-name" value={ownerForm.display_name} onChange={(event) => setOwnerForm({ ...ownerForm, display_name: event.target.value })} required /></div>
            <div><Label htmlFor="owner-type">نوع المالك</Label><select id="owner-type" className="h-12 w-full rounded-md border border-line bg-surface px-3 text-sm" value={ownerForm.party_type} onChange={(event) => setOwnerForm({ ...ownerForm, party_type: event.target.value as OwnerInput['party_type'] })}><option value="individual">فرد</option><option value="legal_entity">جهة قانونية</option></select></div>
            <div><Label htmlFor="owner-mobile">الهاتف</Label><Input id="owner-mobile" value={ownerForm.mobile ?? ''} onChange={(event) => setOwnerForm({ ...ownerForm, mobile: event.target.value })} /></div>
            <div><Label htmlFor="owner-email">البريد الإلكتروني</Label><Input id="owner-email" type="email" value={ownerForm.email ?? ''} onChange={(event) => setOwnerForm({ ...ownerForm, email: event.target.value })} /></div>
            <div className="flex flex-wrap items-center gap-3 md:col-span-2"><Button type="submit" disabled={saveOwner.isPending}>{saveOwner.isPending ? 'جارٍ الحفظ...' : editingOwnerId === null ? 'إضافة مالك' : 'حفظ تعديلات المالك'}</Button>{editingOwnerId !== null && <Button type="button" variant="ghost" onClick={() => { setEditingOwnerId(null); setOwnerForm(emptyOwner); }}>إلغاء</Button>}</div>
          </form>
          {owners.data?.length === 0 ? <div className="mt-6"><EmptyState title="لا يوجد ملاك" description="أضف مالكًا من خلال عقد BF013 المعتمد." /></div> : <div className="mt-6 space-y-3">{owners.data?.map((owner) => <div key={owner.id} className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-line p-4"><button type="button" className="text-right" onClick={() => setSelectedOwnerId(owner.id)}><p className="font-semibold text-ink">{owner.display_name}</p><p className="mt-1 text-xs text-muted">{owner.party_type} · {owner.status}</p></button><div className="flex gap-2"><Button type="button" variant="ghost" onClick={() => { setEditingOwnerId(owner.id); setOwnerForm({ party_type: owner.party_type, display_name: owner.display_name, mobile: owner.mobile, email: owner.email, preferred_contact_method: owner.preferred_contact_method, contact_person_name: owner.contact_person_name, contact_person_mobile: owner.contact_person_mobile, contact_person_email: owner.contact_person_email }); }}>تعديل</Button><Button type="button" variant="outline" disabled={ownerLifecycle.isPending} onClick={() => ownerLifecycle.mutate({ owner, activate: owner.status === 'inactive' })}>{owner.status === 'active' ? 'تعطيل' : 'تفعيل'}</Button></div></div>)}</div>}
          {selectedOwnerId !== null && <p className="mt-4 text-sm text-muted">{selectedOwner.isLoading ? 'جارٍ تحميل تفاصيل المالك...' : selectedOwner.data ? `المالك المحدد: ${selectedOwner.data.display_name}` : 'تعذر تحميل تفاصيل المالك.'}</p>}
        </CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader><h2 className="font-semibold text-ink">الملكية الحالية / Current Ownership</h2></CardHeader>
        <CardContent>
          {currentMissing ? (
            <div>
              <p className="text-sm text-muted">لا توجد ملكية حالية. سجّل الأطراف ونسب الملكية من خلال BF013.</p>
              {createOwnership.error && <div className="mt-4"><Alert>{messageFor(createOwnership.error, 'Ownership could not be created.')}</Alert></div>}
              <div className="mt-5 grid gap-3 md:grid-cols-[1fr_10rem_auto]"><select className="h-12 rounded-md border border-line bg-surface px-3 text-sm" value={newPartyOwnerId ?? ''} onChange={(event) => setNewPartyOwnerId(event.target.value === '' ? null : Number(event.target.value))}><option value="">اختر مالكًا</option>{activeOwners.filter((owner) => !draftOwnerIds.has(owner.id)).map((owner) => <option key={owner.id} value={owner.id}>{owner.display_name}</option>)}</select><Input inputMode="decimal" placeholder="النسبة %" value={newPartyShare} onChange={(event) => setNewPartyShare(event.target.value)} /><Button type="button" variant="outline" disabled={newPartyOwnerId === null || newPartyShare === ''} onClick={() => { setDraftParties([...draftParties, { owner_id: newPartyOwnerId!, share_percentage: newPartyShare }]); setNewPartyOwnerId(null); setNewPartyShare(''); }}>إضافة طرف</Button></div>
              {draftParties.length > 0 && <div className="mt-4 space-y-2">{draftParties.map((party) => <div key={party.owner_id} className="flex justify-between rounded-md border border-line p-3 text-sm"><span>{owners.data?.find((owner) => owner.id === party.owner_id)?.display_name}</span><span>{party.share_percentage}%</span></div>)}</div>}
              <Button className="mt-5" disabled={draftParties.length === 0 || createOwnership.isPending} onClick={() => createOwnership.mutate()}>{createOwnership.isPending ? 'جارٍ التسجيل...' : 'تسجيل الملكية الحالية'}</Button>
            </div>
          ) : currentOwnership ? (
            <CurrentOwnershipPanel ownershipId={currentOwnership.id} parties={parties.data ?? []} owners={owners.data ?? []} newPartyOwnerId={newPartyOwnerId} newPartyShare={newPartyShare} setNewPartyOwnerId={setNewPartyOwnerId} setNewPartyShare={setNewPartyShare} addParty={addParty} updateParty={updateParty} removeParty={removeParty} closeOwnership={closeOwnership} actingOwner={actingOwner.data} actingOwnerMissing={actingOwner.error instanceof ApiClientError && actingOwner.error.status === 404} actingHistory={actingHistory.data ?? []} actingPartyId={actingPartyId} setActingPartyId={setActingPartyId} actingBasis={actingBasis} setActingBasis={setActingBasis} actingNotes={actingNotes} setActingNotes={setActingNotes} saveActingOwner={saveActingOwner} clearActingOwner={clearActingOwner} partyOwner={partyOwner} />
          ) : <Alert>{messageFor(current.error, 'Current Ownership could not be loaded.')}</Alert>}
        </CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader><h2 className="font-semibold text-ink">سجل الملكية / Ownership history</h2></CardHeader>
        <CardContent>{history.data?.length === 0 ? <EmptyState title="لا يوجد سجل ملكية" description="ستظهر سجلات BF013 التاريخية هنا." /> : <div className="space-y-3">{history.data?.map((ownership) => <div key={ownership.id} className="rounded-md border border-line p-4"><p className="font-semibold text-ink">ملكية #{ownership.id} · {ownership.status}</p><p className="mt-1 text-xs text-muted">{ownership.closed_at ? `أغلقت في ${ownership.closed_at}` : 'الملكية الحالية'}</p></div>)}</div>}</CardContent>
      </Card>

      <div className="mt-7"><p className="text-sm text-muted">المراجعة والصور لم تُنفذ بعد في AF004.4. لا يتم إنشاء Listing أو Media من هذه الخطوة.</p></div>
    </main>
  );
}

type MutationState = { isPending: boolean; error: Error | null };

type CurrentPanelProps = {
  ownershipId: number;
  parties: OwnershipParty[];
  owners: Owner[];
  newPartyOwnerId: number | null;
  newPartyShare: string;
  setNewPartyOwnerId: (value: number | null) => void;
  setNewPartyShare: (value: string) => void;
  addParty: MutationState & { mutate: () => void };
  updateParty: MutationState & { mutate: (party: OwnershipParty) => void };
  removeParty: MutationState & { mutate: (partyId: number) => void };
  closeOwnership: MutationState & { mutate: () => void };
  actingOwner: { ownership_party_id: number; basis_source: string } | undefined;
  actingOwnerMissing: boolean;
  actingHistory: Array<{ id: number; ownership_party_id: number; basis_source: string; ended_at: string | null }>;
  actingPartyId: number | null;
  setActingPartyId: (value: number | null) => void;
  actingBasis: string;
  setActingBasis: (value: string) => void;
  actingNotes: string;
  setActingNotes: (value: string) => void;
  saveActingOwner: MutationState & { mutate: () => void };
  clearActingOwner: MutationState & { mutate: () => void };
  partyOwner: (party: OwnershipParty) => Owner | undefined;
};
function CurrentOwnershipPanel(props: CurrentPanelProps) {
  const { parties, owners, partyOwner } = props;
  const selectableOwners = owners.filter((owner) => owner.status === 'active' && !parties.some((party) => party.owner_id === owner.id));
  return <div>
    {props.closeOwnership.error && <Alert>{messageFor(props.closeOwnership.error, 'Ownership could not be closed.')}</Alert>}
    {props.addParty.error && <div className="mt-4"><Alert>{messageFor(props.addParty.error, 'Ownership Party could not be added.')}</Alert></div>}
    {props.updateParty.error && <div className="mt-4"><Alert>{messageFor(props.updateParty.error, 'Ownership Party share could not be updated.')}</Alert></div>}
    {props.removeParty.error && <div className="mt-4"><Alert>{messageFor(props.removeParty.error, 'Ownership Party could not be removed.')}</Alert></div>}
    <div className="mt-4 space-y-3">{parties.map((party) => <PartyRow key={party.id} party={party} name={partyOwner(party)?.display_name ?? `Owner #${party.owner_id}`} onSave={(share) => props.updateParty.mutate({ ...party, share_percentage: share })} onRemove={() => props.removeParty.mutate(party.id)} />)}</div>
    <div className="mt-5 grid gap-3 md:grid-cols-[1fr_10rem_auto]"><select className="h-12 rounded-md border border-line bg-surface px-3 text-sm" value={props.newPartyOwnerId ?? ''} onChange={(event) => props.setNewPartyOwnerId(event.target.value === '' ? null : Number(event.target.value))}><option value="">إضافة مالك مشارك</option>{selectableOwners.map((owner) => <option key={owner.id} value={owner.id}>{owner.display_name}</option>)}</select><Input inputMode="decimal" placeholder="النسبة %" value={props.newPartyShare} onChange={(event) => props.setNewPartyShare(event.target.value)} /><Button type="button" variant="outline" disabled={props.newPartyOwnerId === null || props.newPartyShare === '' || props.addParty.isPending} onClick={() => props.addParty.mutate()}>إضافة طرف ملكية</Button></div>
    <section className="mt-8 border-t border-line pt-6"><h3 className="font-semibold text-ink">المالك المتصرف / Acting Owner</h3>{props.saveActingOwner.error && <div className="mt-4"><Alert>{messageFor(props.saveActingOwner.error, 'Acting Owner could not be saved.')}</Alert></div>}{props.clearActingOwner.error && <div className="mt-4"><Alert>{messageFor(props.clearActingOwner.error, 'Acting Owner could not be cleared.')}</Alert></div>}<p className="mt-2 text-sm text-muted">{props.actingOwner ? `طرف الملكية الحالي: ${partyOwner(parties.find((party) => party.id === props.actingOwner?.ownership_party_id)!)?.display_name ?? props.actingOwner.ownership_party_id}` : props.actingOwnerMissing ? 'لا يوجد مالك متصرف حالي.' : 'جارٍ تحميل المالك المتصرف...'}</p><div className="mt-4 grid gap-3 md:grid-cols-3"><select className="h-12 rounded-md border border-line bg-surface px-3 text-sm" value={props.actingPartyId ?? ''} onChange={(event) => props.setActingPartyId(event.target.value === '' ? null : Number(event.target.value))}><option value="">اختر طرف الملكية</option>{parties.map((party) => <option key={party.id} value={party.id}>{partyOwner(party)?.display_name ?? `Owner #${party.owner_id}`}</option>)}</select><Input placeholder="مصدر التفويض" value={props.actingBasis} onChange={(event) => props.setActingBasis(event.target.value)} /><Input placeholder="ملاحظات اختيارية" value={props.actingNotes} onChange={(event) => props.setActingNotes(event.target.value)} /></div><div className="mt-3 flex flex-wrap gap-3"><Button type="button" disabled={props.actingPartyId === null || props.actingBasis === '' || props.saveActingOwner.isPending} onClick={() => props.saveActingOwner.mutate()}>{props.actingOwner ? 'تغيير المالك المتصرف' : 'تعيين المالك المتصرف'}</Button>{props.actingOwner && <Button type="button" variant="outline" disabled={props.clearActingOwner.isPending} onClick={() => props.clearActingOwner.mutate()}>إلغاء التعيين</Button>}</div>{props.actingHistory.length > 0 && <div className="mt-4 space-y-2 text-xs text-muted">{props.actingHistory.map((entry) => <p key={entry.id}>طرف #{entry.ownership_party_id} · {entry.basis_source} · {entry.ended_at ? 'منتهٍ' : 'حالي'}</p>)}</div>}</section>
    <Button type="button" className="mt-8" variant="danger" disabled={props.closeOwnership.isPending} onClick={() => props.closeOwnership.mutate()}>إغلاق الملكية الحالية</Button>
  </div>;
}

function PartyRow({ party, name, onSave, onRemove }: { party: OwnershipParty; name: string; onSave: (share: string) => void; onRemove: () => void }) {
  const [share, setShare] = useState(party.share_percentage);
  return <div className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-line p-4"><p className="font-semibold text-ink">{name}</p><div className="flex items-center gap-2"><Input className="w-28" inputMode="decimal" value={share} onChange={(event) => setShare(event.target.value)} /><Button type="button" variant="ghost" onClick={() => onSave(share)}>حفظ النسبة</Button><Button type="button" variant="ghost" onClick={onRemove}>حذف</Button></div></div>;
}
