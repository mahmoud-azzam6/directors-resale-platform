import { AuthorizedPlaceholderPage } from '@/components/layout/authorized-placeholder-page';
export default function UsersPage() { return <AuthorizedPlaceholderPage permission="users.view" section="People and access" title="Users" description="A focused workspace for the people who operate across your Directors network." />; }
