import { PropertyReviewPage } from '@/features/properties/property-review-page';
export default function PropertyReviewRoute({ params }: { params: { id: string } }) { return <PropertyReviewPage id={params.id} />; }
