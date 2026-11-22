import { HelpCircle } from "lucide-react";
import { useId } from "react";
import { SIMILARITY_HELP } from "@/lib/wording";

/**
 * "Similarity score" with a help tip (spec §8: the raw score is shown, never as a percentage). The tip opens
 * on hover and on keyboard focus, and is read by screen readers through aria-describedby.
 */
export function SimilarityScoreLabel({ text = "Similarity score" }: { text?: string }) {
  const id = useId();
  return (
    <span className="group relative inline-flex items-center gap-1 align-baseline">
      {text}
      <button
        type="button"
        aria-label="What the similarity score means"
        aria-describedby={id}
        className="inline-flex size-4 items-center justify-center rounded-full text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand"
      >
        <HelpCircle aria-hidden="true" size={13} />
      </button>
      <span
        id={id}
        role="tooltip"
        className="pointer-events-none invisible absolute bottom-full left-0 z-30 mb-2 w-64 rounded-md bg-foreground px-3 py-2 text-xs leading-relaxed font-normal text-background opacity-0 shadow-lg transition-opacity duration-150 group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100"
      >
        {SIMILARITY_HELP}
      </span>
    </span>
  );
}
