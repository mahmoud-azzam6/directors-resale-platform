import { AuthorizedPlaceholderPage } from '@/components/layout/authorized-placeholder-page';
export default function OrganizationsPage() { return <AuthorizedPlaceholderPage permission="organizations.view" section="Organization management" title="Organizations" description="View and manage the organization structure that anchors your platform scope." />; }
