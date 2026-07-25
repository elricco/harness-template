// GET /api/form-token
// Liefert ein frisches, signiertes Zeit-Token. Der Client holt es beim
// Laden des Formulars; damit startet die "Mindestverweildauer".
import type { APIRoute } from "astro";
import { issueToken } from "../../lib/formToken";

export const prerender = false;

export const GET: APIRoute = async () => {
  const token = await issueToken(import.meta.env.FORM_SECRET);
  return new Response(JSON.stringify({ token }), {
    status: 200,
    headers: {
      "Content-Type": "application/json",
      "Cache-Control": "no-store", // nie cachen -> Token bleibt frisch
    },
  });
};
