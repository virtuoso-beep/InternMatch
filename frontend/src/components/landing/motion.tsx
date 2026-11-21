import { useEffect, useRef, type CSSProperties, type ElementType, type ReactNode } from "react";
import { cx } from "@/components/im/ui";

const reducedMotion = () =>
  typeof window !== "undefined" && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

/**
 * Fades and lifts its content into view on scroll. Content already on screen at load is never hidden,
 * and nothing is hidden without JavaScript or with reduced motion.
 */
export function Reveal({
  as: Tag = "div",
  index = 0,
  className,
  children,
}: {
  as?: ElementType;
  index?: number;
  className?: string | undefined;
  children: ReactNode;
}) {
  const ref = useRef<HTMLElement>(null);
  useEffect(() => {
    const node = ref.current;
    if (!node || reducedMotion() || !("IntersectionObserver" in window)) return;
    if (node.getBoundingClientRect().top < window.innerHeight) return;
    node.dataset["reveal"] = "pending";
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (!entry?.isIntersecting) return;
        node.dataset["reveal"] = "shown";
        observer.disconnect();
      },
      { rootMargin: "0px 0px -10% 0px" },
    );
    observer.observe(node);
    return () => observer.disconnect();
  }, []);
  return (
    <Tag
      ref={ref}
      className={cx("im-reveal", className)}
      style={{ "--stagger": index } as CSSProperties}
    >
      {children}
    </Tag>
  );
}
