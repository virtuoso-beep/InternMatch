import { Link } from "@tanstack/react-router";
import { ArrowRight } from "lucide-react";
import { Seal } from "./Brand";
import { CampusPhoto } from "./CampusPhoto";
import { COLLEGE, NAV, PHOTOS, PHOTO_CREDIT } from "./content";
import { Reveal } from "./motion";
import { Eyebrow } from "./SectionHeading";

/** Asymmetric mosaic of campus and practicum photographs, in two balanced columns. */
export function PhotoMosaic() {
  return (
    <section aria-labelledby="gallery-title" className="bg-background">
      <div className="mx-auto max-w-7xl px-4 py-24 sm:px-8 lg:py-32">
        <Reveal className="flex flex-wrap items-end justify-between gap-6">
          <div>
            <Eyebrow number="05" label="Campus and practicum" />
            <h2 id="gallery-title" className="mt-6 font-serif text-headline font-medium">
              Life at UM Tagum College.
            </h2>
          </div>
        </Reveal>
        <div className="mt-12 gap-4 sm:columns-2">
          {PHOTOS.mosaic.map((photo, index) => (
            <Reveal
              key={photo.need}
              index={index % 2}
              className="mb-4 break-inside-avoid overflow-hidden rounded-lg"
            >
              <CampusPhoto
                photo={photo}
                sizes="(min-width: 640px) 50vw, 100vw"
                className="transition-transform duration-slow ease-cinematic hover:scale-102"
              />
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

export function ClosingCta() {
  return (
    <section
      aria-labelledby="cta-title"
      className="header-gradient relative overflow-hidden text-header-foreground"
    >
      <div className="mx-auto flex max-w-7xl flex-col items-start gap-10 px-4 py-24 sm:px-8 lg:flex-row lg:items-end lg:justify-between lg:py-32">
        <Reveal className="max-w-3xl">
          <Eyebrow number="06" label="Get started" tone="inverse" />
          <h2 id="cta-title" className="mt-6 font-serif text-headline font-medium text-balance">
            Your practicum starts with your profile.
          </h2>
          <p className="mt-6 max-w-prose text-lg leading-relaxed text-header-foreground/85">
            Sign in with the account issued by the college. If you have not received one, contact
            your practicum coordinator.
          </p>
        </Reveal>
        <Reveal index={1}>
          <Link
            to="/auth"
            className="group inline-flex h-14 items-center gap-2 rounded-md bg-header-foreground px-8 text-base font-semibold text-header shadow-lg transition-[transform,background-color] duration-fast ease-cinematic hover:-translate-y-0.5 hover:bg-header-foreground/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warn active:translate-y-0"
          >
            Log in to InternMatch
            <ArrowRight
              aria-hidden="true"
              size={18}
              className="transition-transform duration-fast group-hover:translate-x-0.5"
            />
          </Link>
        </Reveal>
      </div>
    </section>
  );
}

export function SiteFooter() {
  return (
    <footer className="border-t border-border bg-card">
      <div className="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-8 md:grid-cols-12">
        <div className="flex items-start gap-4 md:col-span-6">
          <Seal className="size-12" />
          <div>
            <p className="font-serif text-title font-medium">{COLLEGE.name}</p>
            <address className="mt-2 text-sm leading-relaxed text-muted-foreground not-italic">
              {COLLEGE.address}
            </address>
          </div>
        </div>
        <nav aria-label="Footer" className="md:col-span-3">
          <p className="text-eyebrow font-semibold uppercase text-muted-foreground">InternMatch</p>
          <ul className="mt-4 space-y-2 text-sm">
            {NAV.map((item) => (
              <li key={item.href}>
                <a
                  href={item.href}
                  className="text-foreground transition-colors duration-fast hover:text-brand"
                >
                  {item.label}
                </a>
              </li>
            ))}
            <li>
              <Link
                to="/auth"
                className="text-foreground transition-colors duration-fast hover:text-brand"
              >
                Log in
              </Link>
            </li>
          </ul>
        </nav>
        <p className="text-sm leading-relaxed text-muted-foreground md:col-span-3">
          InternMatch is the internship placement and monitoring system of {COLLEGE.short}.
          Recommendations support, and never replace, the practicum coordinator's decision.
        </p>
      </div>
      <div className="border-t border-border">
        <p className="mx-auto max-w-7xl px-4 py-6 text-xs text-muted-foreground sm:px-8">
          © {new Date().getFullYear()} {COLLEGE.name}. InternMatch. {PHOTO_CREDIT}.
        </p>
      </div>
    </footer>
  );
}
