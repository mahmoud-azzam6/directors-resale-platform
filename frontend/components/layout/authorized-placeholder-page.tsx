import { ForbiddenState } from '@/components/ui/forbidden-state';
import { getServerAuthContext } from '@/lib/server-auth';
import { PlaceholderPage } from './placeholder-page';

export async function AuthorizedPlaceholderPage({ permission, section, title, description }: { permission: string; section: string; title: string; description: string }) {
  const context = await getServerAuthContext();
  if (!context || !context.permissions.includes(permission)) return <main className="page-shell"><ForbiddenState /></main>;
  return <PlaceholderPage section={section} title={title} description={description} />;
}
