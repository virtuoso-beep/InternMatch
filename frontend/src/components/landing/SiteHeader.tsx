import { Link } from "@tanstack/react-router";
import { Menu, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { cx } from "@/components/im/ui";
import { Seal, Wordmark } from "./Brand";
import { NAV } from "./content";

/**
 * Transparent over the hero, solid once the page scrolls. On small screens the navigation opens as an
 * animated panel: Escape closes it, focus moves into it and returns to the menu button.
 */
export function SiteHeader() {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);
  const button = useRef<HTMLButtonElement>(null);
  const panel = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 24);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    if (!open) return;
    panel.current?.querySelector<HTMLElement>("a")?.focus();
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setOpen(false);
        button.current?.focus();
      }
    };
    const close = () => setOpen(false);
    document.addEventListener("keydown", onKey);
    window.addEventListener("resize", close);
    return () => {
      document.removeEventListener("keydown", onKey);
      window.removeEventListener("resize", close);
    };
  }, [open]);

  const solid = scrolled || open;
  const linkTone = solid
    ? "text-foreground hover:text-brand"
    : "text-header-foreground hover:text-warn-light";

  return (
    <header
      className={cx(
        "fixed inset-x-0 top-0 z-40 transition-[background-color,box-shadow,color] duration-base ease-cinematic",
        solid ? "bg-card/95 shadow-sm backdrop-blur" : "bg-transparent",
      )}
    >
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-8 lg:h-20">
        <a
          href="#top"
          className="flex items-center gap-3 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-warn"
        >
          <Seal />
          <span className={solid ? "text-foreground" : "text-header-foreground"}>
            <Wordmark tone={solid ? "dark" : "light"} />
          </span>
        </a>

        <nav aria-label="Main" className="hidden items-center gap-8 md:flex">
          {NAV.map((item) => (
            <a
              key={item.href}
              href={item.href}
              className={cx(
                "text-sm font-medium transition-colors duration-fast focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-warn",
                linkTone,
              )}
            >
              {item.label}
            </a>
          ))}
          <Link
            to="/auth"
            className={cx(
              "rounded-md px-4 py-2 text-sm font-semibold transition-colors duration-fast focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warn",
              solid
                ? "bg-header text-header-foreground hover:bg-header/90"
                : "bg-header-foreground text-header hover:bg-header-foreground/90",
            )}
          >
            Log in
          </Link>
        </nav>

        <button
          ref={button}
          type="button"
          className={cx(
            "inline-flex size-11 items-center justify-center rounded-md md:hidden focus-visible:outline-2 focus-visible:outline-warn",
            solid ? "text-foreground" : "text-header-foreground",
          )}
          aria-expanded={open}
          aria-controls="mobile-menu"
          aria-label={open ? "Close menu" : "Open menu"}
          onClick={() => setOpen((value) => !value)}
        >
          {open ? <X aria-hidden="true" size={22} /> : <Menu aria-hidden="true" size={22} />}
        </button>
      </div>

      <div
        id="mobile-menu"
        ref={panel}
        hidden={!open}
        className="border-t border-border bg-card md:hidden"
      >
        <nav
          aria-label="Main"
          className="animate-in fade-in slide-in-from-top-2 px-4 pt-2 pb-6 duration-300"
        >
          <ul>
            {NAV.map((item) => (
              <li key={item.href} className="border-b border-border">
                <a
                  href={item.href}
                  className="block py-4 font-serif text-2xl text-foreground focus-visible:outline-2 focus-visible:outline-warn"
                  onClick={() => setOpen(false)}
                >
                  {item.label}
                </a>
              </li>
            ))}
          </ul>
          <Link
            to="/auth"
            className="mt-6 flex h-12 items-center justify-center rounded-md bg-header text-sm font-semibold text-header-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warn"
          >
            Log in
          </Link>
        </nav>
      </div>
    </header>
  );
}
