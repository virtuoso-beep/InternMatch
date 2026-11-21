import { createFileRoute } from "@tanstack/react-router";
import { useEffect } from "react";
import newsreaderLatin from "@fontsource-variable/newsreader/files/newsreader-latin-opsz-normal.woff2?url";
import { About } from "@/components/landing/About";
import { ClosingCta, PhotoMosaic, SiteFooter } from "@/components/landing/Closing";
import {
  PHOTOS,
  photoDefaultWidth,
  photoSrcSet,
  photoUrl,
  type PhotoSlot,
} from "@/components/landing/content";
import { Hero } from "@/components/landing/Hero";
import { HowItWorks } from "@/components/landing/HowItWorks";
import { MatchingExplainer } from "@/components/landing/MatchingExplainer";
import { Roles } from "@/components/landing/Roles";
import { SiteHeader } from "@/components/landing/SiteHeader";

const TITLE = "InternMatch | UM Tagum College internship placement";
const DESCRIPTION =
  "InternMatch connects University of Mindanao Tagum College students with the right internship: profiles, AI-assisted matching, coordinator-approved placement and practicum monitoring in one system.";

const hero: PhotoSlot = PHOTOS.hero;

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: TITLE },
      { name: "description", content: DESCRIPTION },
      { property: "og:title", content: TITLE },
      { property: "og:description", content: DESCRIPTION },
      { property: "og:type", content: "website" },
      // The Open Graph image is the real hero photograph once it is supplied (see landing/content.ts).
      ...(hero.file
        ? [
            { property: "og:image", content: photoUrl(hero, photoDefaultWidth(hero), "jpg") },
            { property: "og:image:alt", content: PHOTOS.hero.alt },
          ]
        : []),
      { name: "twitter:card", content: hero.file ? "summary_large_image" : "summary" },
    ],
    links: [
      // The hero headline is the largest paint; fetch its display font early.
      {
        rel: "preload",
        as: "font",
        type: "font/woff2",
        href: newsreaderLatin,
        crossOrigin: "anonymous",
      },
      ...(hero.file
        ? [
            {
              rel: "preload",
              as: "image",
              type: hero.avif ? "image/avif" : "image/webp",
              imageSrcSet: photoSrcSet(hero, hero.avif ? "avif" : "webp"),
              imageSizes: "100vw",
              fetchPriority: "high" as const,
            },
          ]
        : []),
    ],
  }),
  component: Landing,
});

function Landing() {
  useEffect(() => {
    const root = document.documentElement;
    root.classList.add("im-no-scrollbar");
    return () => root.classList.remove("im-no-scrollbar");
  }, []);
  return (
    <div className="min-h-screen bg-background text-foreground">
      <a
        href="#main"
        className="sr-only z-50 rounded-md bg-card px-4 py-2 text-sm font-semibold text-foreground shadow-lg focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
      >
        Skip to content
      </a>
      <SiteHeader />
      <main id="main" tabIndex={-1} className="focus:outline-none">
        <Hero />
        <About />
        <HowItWorks />
        <Roles />
        <MatchingExplainer />
        <PhotoMosaic />
        <ClosingCta />
      </main>
      <SiteFooter />
    </div>
  );
}
