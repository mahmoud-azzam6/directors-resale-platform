import { ListingDetailPage } from '@/features/listings/listing-pages';
export default function ListingPage({ params }: { params: { id: string } }) { return <ListingDetailPage id={params.id} />; }
