import { Link } from "@tanstack/react-router";
import { ArrowDown, ArrowRight, MapPin } from "lucide-react";
import type { CSSProperties } from "react";
import { CampusPhoto } from "./CampusPhoto";
import { PHOTOS } from "./content";

const rise = (index: number) => ({ "--stagger": index }) as CSSProperties;

/**
 * An illustrative recommendation card. The values are an example, not a real student; the score is the
 * raw cosine similarity to two decimals and the distance is straight-line (Haversine), as in the system.
 */
function MatchPreview() {
  return (
    <figure
      className="im-rise w-full max-w-sm rounded-lg bg-card/95 p-6 text-card-foreground shadow-2xl ring-1 ring-header-foreground/10"
      style={rise(4)}
      aria-label="Example recommendation card"
    >
      <figcaption className="flex items-center justify-between text-eyebrow font-semibold uppercase">
        <span className="text-warn-ink">Recommendation</span>
        <span className="text-muted-foreground">Example</span>
      </figcaption>
      <p className="mt-4 font-serif text-title">Operations Assistant</p>
      <p className="mt-1 text-sm text-muted-foreground">Host establishment in Tagum City</p>
      <dl className="mt-6 grid grid-cols-2 gap-4 border-t border-border pt-4">
        <div>
          <dt className="text-xs text-muted-foreground">Similarity score</dt>
          <dd className="mt-1 text-2xl font-semibold tabular-nums">0.82</dd>
        </div>
        <div>
          <dt className="text-xs text-muted-foreground">Distance</dt>
          <dd className="mt-1 flex items-baseline gap-1 text-2xl font-semibold tabular-nums">
            4.6<span className="text-sm font-medium text-muted-foreground">km</span>
          </dd>
        </div>
      </dl>
      <p className="mt-4 flex items-center gap-2 text-xs text-muted-foreground">
        <MapPin aria-hidden="true" size={14} className="text-warn-ink" />
        Straight-line distance. Final decision by the coordinator.
      </p>
    </figure>
  );
}

export function Hero() {
  return (
    <section
      id="top"
      aria-labelledby="hero-title"
      className="relative isolate min-h-svh overflow-hidden bg-header"
    >
      <div className="absolute inset-0 -z-20">
        <CampusPhoto
          photo={PHOTOS.hero}
          sizes="100vw"
          priority
          fill
          tone="dark"
          imageClassName="im-kenburns"
        />
      </div>
      <div aria-hidden="true" className="photo-scrim absolute inset-0 -z-10" />

      <div className="mx-auto flex min-h-svh max-w-7xl flex-col justify-end gap-12 px-4 pt-32 pb-16 sm:px-8 lg:flex-row lg:items-end lg:justify-between lg:pb-24">
        <div className="max-w-3xl text-header-foreground">
          <p
            className="im-rise flex items-center gap-4 text-eyebrow font-semibold uppercase text-warn-light"
            style={rise(0)}
          >
            <span aria-hidden="true" className="h-px w-8 bg-current" />
            University of Mindanao Tagum College
          </p>
          <h1 id="hero-title" className="mt-6 font-serif text-display font-medium text-balance">
            The right internship for every UM&nbsp;Tagum student.
          </h1>
          <p
            className="im-rise mt-8 max-w-prose text-lg leading-relaxed text-header-foreground/90"
            style={rise(1)}
          >
            InternMatch brings student profiles, host establishments, placement and monitoring into
            one system, so each student is placed where their skills fit.
          </p>
          <div className="im-rise mt-10 flex flex-wrap gap-4" style={rise(2)}>
            <Link
              to="/auth"
              className="group inline-flex h-12 items-center gap-2 rounded-md bg-header-foreground px-6 text-sm font-semibold text-header shadow-lg transition-[transform,background-color] duration-fast ease-cinematic hover:-translate-y-0.5 hover:bg-header-foreground/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warn active:translate-y-0"
            >
              Log in to InternMatch
              <ArrowRight
                aria-hidden="true"
                size={16}
                className="transition-transform duration-fast group-hover:translate-x-0.5"
              />
            </Link>
            <a
              href="#how-it-works"
              className="inline-flex h-12 items-center gap-2 rounded-md border border-header-foreground/40 px-6 text-sm font-semibold text-header-foreground transition-colors duration-fast hover:border-warn-light hover:text-warn-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warn"
            >
              See how it works
              <ArrowDown aria-hidden="true" size={16} />
            </a>
          </div>
        </div>
        <MatchPreview />
      </div>
    </section>
  );
}
