'use client';

import { useState } from 'react';

export default function AffiliateImage({ src, alt }: { src: string; alt: string }) {
  const [hasError, setHasError] = useState(false);

  // Jika gambar gagal dimuat, jangan tampilkan apa-apa (atau tampilkan placeholder)
  if (hasError) return null;

  return (
    <img
      src={src}
      alt={alt}
      loading="lazy"
      onError={() => setHasError(true)}
    />
  );
}