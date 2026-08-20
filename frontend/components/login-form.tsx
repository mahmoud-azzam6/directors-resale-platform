'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { ArrowRight, Eye, EyeOff, LockKeyhole, Mail } from 'lucide-react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Alert } from '@/components/ui/alert';

const schema = z.object({ email: z.string().email('Enter a valid email address.'), password: z.string().min(1, 'Enter your password.') });
type FormValues = z.infer<typeof schema>;

export function LoginForm() {
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm<FormValues>({ resolver: zodResolver(schema) });

  useEffect(() => { fetch('/api/auth/context', { credentials: 'include' }).then((response) => { if (response.ok) router.replace('/admin'); }).catch(() => undefined); }, [router]);

  async function onSubmit(values: FormValues) {
    setError('');
    const response = await fetch('/api/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(values) });
    if (!response.ok) { setError('We could not sign you in with those details.'); return; }
    router.replace('/admin');
    router.refresh();
  }

  return <form className="mt-8 space-y-5" onSubmit={handleSubmit(onSubmit)} noValidate>{error && <Alert>{error}</Alert>}<div><Label htmlFor="email">Email address</Label><div className="relative"><Mail size={17} className="pointer-events-none absolute left-3.5 top-3.5 text-muted" /><Input id="email" type="email" autoComplete="email" placeholder="you@company.com" className="pl-11" {...register('email')} /></div>{errors.email && <p className="mt-2 text-xs text-danger">{errors.email.message}</p>}</div><div><div className="flex items-center justify-between"><Label htmlFor="password">Password</Label></div><div className="relative"><LockKeyhole size={17} className="pointer-events-none absolute left-3.5 top-3.5 text-muted" /><Input id="password" type={showPassword ? 'text' : 'password'} autoComplete="current-password" placeholder="Enter your password" className="pl-11 pr-11" {...register('password')} /><button type="button" aria-label={showPassword ? 'Hide password' : 'Show password'} onClick={() => setShowPassword((value) => !value)} className="absolute right-3.5 top-3.5 text-muted hover:text-ink">{showPassword ? <EyeOff size={17} /> : <Eye size={17} />}</button></div>{errors.password && <p className="mt-2 text-xs text-danger">{errors.password.message}</p>}</div><Button type="submit" className="h-12 w-full gap-2" disabled={isSubmitting}>{isSubmitting ? 'Signing in…' : <>Continue to workspace <ArrowRight size={17} /></>}</Button></form>;
}
