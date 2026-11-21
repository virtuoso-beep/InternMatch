import { CampusPhoto } from "./CampusPhoto";
import { PHOTOS } from "./content";
import { Reveal } from "./motion";
import { useParallax } from "./useParallax";
import { SectionHeading } from "./SectionHeading";

export function About() {
  const photo = useParallax<HTMLDivElement>(0.05);
  return (
    <section id="about" aria-labelledby="about-title" className="scroll-mt-20 bg-background">
      <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-24 sm:px-8 lg:grid-cols-12 lg:gap-16 lg:py-32">
        <div className="lg:col-span-6">
          <SectionHeading
            id="about-title"
            number="01"
            label="About the college"
            title="Where classroom learning meets the workplace."
            lead="The University of Mindanao Tagum College prepares its students for work in the region and beyond. The practicum is where that preparation is tested: students apply what they have learned under the guidance of a host establishment."
          />
          <Reveal
            index={1}
            className="mt-8 max-w-prose space-y-4 text-base leading-relaxed text-muted-foreground"
          >
            <p>
              A good placement depends on matching each student with work that fits their skills,
              within reach of where they live, at a host with an active agreement and an open slot.
            </p>
            <p>
              InternMatch keeps that information in one place, so coordinators can make the decision
              with the full picture and students can follow their progress from requirements to
              completion.
            </p>
          </Reveal>
        </div>
        <Reveal index={1} className="lg:col-span-5 lg:col-start-8">
          <div className="overflow-hidden rounded-lg shadow-xl">
            <div ref={photo} className="will-change-transform">
              <CampusPhoto
                photo={PHOTOS.about}
                sizes="(min-width: 1024px) 40vw, 100vw"
                className="scale-110"
              />
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
