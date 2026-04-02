"use client"

import { GlobePulse } from "@/components/ui/cobe-globe-pulse"

export default function GlobePulseDemo() {
  return (
    <div className="flex w-full items-center justify-center bg-black p-6 overflow-hidden">
      <div className="w-full max-w-[280px]">
        <GlobePulse speed={0.0028} />
      </div>
    </div>
  )
}
