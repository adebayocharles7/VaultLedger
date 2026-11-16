import { isAxiosError } from 'axios';
import type { ValidationErrorResponse } from '../api/types';

// Laravel returns 422 with { message, errors: { field: [msgs] } } whenever
// a FormRequest's validation fails. This pulls that out safely, falling
// back to null for any other kind of error (network failure, 500, etc.)
// so the caller can show a generic message instead.
export function getValidationErrors(error: unknown): Record<string, string[]> | null {
  if (
    isAxiosError<ValidationErrorResponse>(error) &&
    error.response?.status === 422 &&
    error.response.data?.errors
  ) {
    return error.response.data.errors;
  }
  return null;
}

export function firstError(
  errors: Record<string, string[]> | null,
  field: string
): string | undefined {
  return errors?.[field]?.[0];
}
