import { useState, type FormEvent } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import { register } from '../api/auth';
import { useAuthStore } from '../store/authStore';
import { getValidationErrors, firstError } from '../lib/validationErrors';

export default function Register() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');

  const setSession = useAuthStore((state) => state.setSession);
  const navigate = useNavigate();

  const mutation = useMutation({
    mutationFn: register,
    onSuccess: (data) => {
      setSession(data.token, data.user);
      navigate('/folios');
    },
  });

  // Field-level errors straight from Laravel's validator — e.g. "email
  // already taken" shows up under the email input, not as one generic
  // banner. Only meaningful once the request has actually failed.
  const validationErrors = mutation.isError
    ? getValidationErrors(mutation.error)
    : null;

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    mutation.mutate({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    });
  }

  return (
    <div className="max-w-sm mx-auto mt-16">
      <h1 className="text-xl font-semibold mb-4">Create an account</h1>
      <form onSubmit={handleSubmit} className="space-y-3">
        <div>
          <input
            type="text"
            placeholder="Full name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="w-full border rounded px-3 py-2"
            required
          />
          {firstError(validationErrors, 'name') && (
            <p className="text-red-600 text-xs mt-1">
              {firstError(validationErrors, 'name')}
            </p>
          )}
        </div>

        <div>
          <input
            type="email"
            placeholder="Email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="w-full border rounded px-3 py-2"
            required
          />
          {firstError(validationErrors, 'email') && (
            <p className="text-red-600 text-xs mt-1">
              {firstError(validationErrors, 'email')}
            </p>
          )}
        </div>

        <div>
          <input
            type="password"
            placeholder="Password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="w-full border rounded px-3 py-2"
            required
          />
          {firstError(validationErrors, 'password') && (
            <p className="text-red-600 text-xs mt-1">
              {firstError(validationErrors, 'password')}
            </p>
          )}
        </div>

        <input
          type="password"
          placeholder="Confirm password"
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
          className="w-full border rounded px-3 py-2"
          required
        />

        <button
          type="submit"
          disabled={mutation.isPending}
          className="w-full bg-black text-white rounded py-2 disabled:opacity-50"
        >
          {mutation.isPending ? 'Creating account…' : 'Create account'}
        </button>

        {/* Fallback for non-validation failures (network error, 500, etc.) */}
        {mutation.isError && !validationErrors && (
          <p className="text-red-600 text-sm">
            Something went wrong. Please try again.
          </p>
        )}
      </form>

      <p className="text-sm text-gray-500 mt-4">
        Already have an account?{' '}
        <Link to="/login" className="underline">
          Sign in
        </Link>
      </p>
    </div>
  );
}
