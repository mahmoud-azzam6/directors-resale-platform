import { OwnershipStepPage } from '@/features/properties/ownership-step-page';
export default function OwnershipStepRoute({ params }: { params: { id: string } }) { return <OwnershipStepPage id={params.id} />; }
