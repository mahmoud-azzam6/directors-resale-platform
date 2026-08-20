import { FranchiseDetailPage } from '@/features/network/franchise-detail-page';

export default function FranchisePage({ params }: { params: { id: string } }) {
  return <FranchiseDetailPage id={params.id} />;
}
