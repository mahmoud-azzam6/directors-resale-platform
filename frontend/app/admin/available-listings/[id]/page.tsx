import { PublishedDetailPage } from '@/features/requests/published-listing-pages';
export default function Page({ params }: { params: { id: string } }) { return <PublishedDetailPage id={params.id} />; }
