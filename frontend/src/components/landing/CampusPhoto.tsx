import { useEffect, useRef, useState } from "react";
import { cx } from "@/components/im/ui";
import {
  photoDefaultWidth,
  photoSrcSet,
  photoUrl,
  type PhotoRatio,
  type PhotoSlot,
} from "./content";

const RATIO_CLASS: Record<PhotoRatio, string> = {
  "4/5": "aspect-[4/5]",
  "3/2": "aspect-[3/2]",
  "16/9": "aspect-video",
};

/**
 * A campus photograph (AVIF when exported, WebP, JPEG via <picture>, srcset, blur-up while loading),
 * or a sized placeholder labelled with the photo still needed. `fill` stretches to the parent instead of
 * keeping the slot's aspect ratio (used by the full-screen hero).
 */
export function CampusPhoto({
  photo,
  sizes,
  priority = false,
  fill = false,
  tone = "light",
  className,
  imageClassName,
}: {
  photo: PhotoSlot;
  sizes: string;
  priority?: boolean;
  fill?: boolean;
  tone?: "light" | "dark";
  className?: string | undefined;
  imageClassName?: string | undefined;
}) {
  const [loaded, setLoaded] = useState(false);
  const image = useRef<HTMLImageElement>(null);
  // An image the browser finished before hydration never fires onLoad for React; check on mount.
  useEffect(() => {
    if (image.current?.complete && image.current.naturalWidth > 0) setLoaded(true);
  }, []);
  const frame = cx(
    "relative overflow-hidden",
    fill ? "size-full" : RATIO_CLASS[photo.ratio],
    className,
  );

  if (!photo.file) {
    return (
      <div
        className={cx(frame, tone === "dark" ? "photo-placeholder-dark" : "photo-placeholder")}
        role="img"
        aria-label={`Photo placeholder: ${photo.need}`}
      >
        <span
          className={cx(
            "absolute right-4 bottom-4 max-w-[80%] rounded-sm px-2 py-1 text-xs font-medium",
            tone === "dark"
              ? "bg-header-foreground/15 text-header-foreground"
              : "bg-card/80 text-muted-foreground",
          )}
        >
          Photo: {photo.need}
        </span>
      </div>
    );
  }

  return (
    <div
      className={frame}
      style={
        photo.blur ? { backgroundImage: `url(${photo.blur})`, backgroundSize: "cover" } : undefined
      }
    >
      <picture className="block size-full">
        {photo.avif && (
          <source type="image/avif" srcSet={photoSrcSet(photo, "avif")} sizes={sizes} />
        )}
        <source type="image/webp" srcSet={photoSrcSet(photo, "webp")} sizes={sizes} />
        <img
          ref={image}
          src={photoUrl(photo, photoDefaultWidth(photo), "jpg")}
          srcSet={photoSrcSet(photo, "jpg")}
          sizes={sizes}
          alt={photo.alt}
          loading={priority ? "eager" : "lazy"}
          fetchPriority={priority ? "high" : "auto"}
          decoding={priority ? "sync" : "async"}
          onLoad={() => setLoaded(true)}
          className={cx(
            "size-full object-cover transition-opacity duration-slow ease-cinematic",
            loaded || priority ? "opacity-100" : "opacity-0",
            imageClassName,
          )}
        />
      </picture>
    </div>
  );
}
