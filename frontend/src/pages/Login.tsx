import { useState, type FormEvent } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import { login } from '../api/auth';
import { useAuthStore } from '../store/authStore';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const setSession = useAuthStore((state) => state.setSession);
  const navigate = useNavigate();

  const mutation = useMutation({
    mutationFn: login,
    onSuccess: (data) => {
      setSession(data.token, data.user);
      navigate('/folios');
    },
  });

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    mutation.mutate({ email, password });
  }

  return (
    <div className="max-w-sm mx-auto mt-20">
      <h1 className="text-xl font-semibold mb-4">Sign in to VaultLedger</h1>
      <form onSubmit={handleSubmit} className="space-y-3">
        <input
          type="email"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="w-full border rounded px-3 py-2"
          required
        />
        <input
          type="password"
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="w-full border rounded px-3 py-2"
          required
        />
        <button
          type="submit"
          disabled={mutation.isPending}
          className="w-full bg-black text-white rounded py-2 disabled:opacity-50"
        >
          {mutation.isPending ? 'Signing in…' : 'Sign in'}
        </button>
        {mutation.isError && (
          <p className="text-red-600 text-sm">Invalid email or password.</p>
        )}
      </form>

      <p className="text-sm text-gray-500 mt-4">
        Don't have an account?{' '}
        <Link to="/register" className="underline">
          Create one
        </Link>
      </p>
    </div>
  );
}
