/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.php",
    "./pages/**/*.php",
    "./app/**/*.php",
    "./src/**/*.{html,js}",
  ],
  safelist: [
    // Color utilities (all palettes x all shades)
    {
      pattern: /^(bg|text|border|ring|from|via|to|divide|fill|stroke|accent|decoration|shadow)-(slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|white|black|transparent|primary|sidebar)-(50|100|200|300|400|500|600|700|800|900|950)$/,
      variants: ['hover', 'focus', 'active', 'disabled', 'sm', 'md', 'lg', 'xl', 'group-hover'],
    },
    // Color utilities with opacity modifier (e.g. bg-white/10, text-white/70)
    {
      pattern: /^(bg|text|border|ring)-(slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|white|black|primary|sidebar)\/(10|20|30|40|50|60|70|80|90)$/,
      variants: ['hover', 'focus', 'active', 'disabled'],
    },
    // Spacing utilities (margin / padding / gap / space)
    {
      pattern: /^(m|mx|my|mt|mb|ml|mr|p|px|py|pt|pb|pl|pr|gap|gap-x|gap-y|space-x|space-y)-(0|0\.5|1|1\.5|2|2\.5|3|4|5|6|7|8|10|12|14|16|20|24|28|32|36|40|44|48|52|56|60|64|72|80|96|px)$/,
      variants: ['hover', 'focus', 'sm', 'md', 'lg', 'xl'],
    },
    // Sizing utilities
    {
      pattern: /^(w|h|min-w|max-w|min-h|max-h)-(0|0\.5|1|1\.5|2|2\.5|3|4|5|6|7|8|10|12|14|16|20|24|28|32|36|40|44|48|52|56|60|64|72|80|96|px|full|screen|auto|sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl)$/,
      variants: ['hover', 'sm', 'md', 'lg', 'xl'],
    },
    // Grid / flex layout
    {
      pattern: /^(grid-cols|col-span|row-span)-(1|2|3|4|5|6|7|8|9|10|11|12)$/,
      variants: ['sm', 'md', 'lg', 'xl'],
    },
    // Typography sizes and weights
    {
      pattern: /^(text-xs|text-sm|text-base|text-lg|text-xl|text-2xl|text-3xl|text-4xl|text-5xl|text-6xl|font-thin|font-light|font-normal|font-medium|font-semibold|font-bold|font-extrabold|leading-none|leading-tight|leading-snug|leading-normal|leading-relaxed|leading-loose|tracking-tight|tracking-normal|tracking-wide|tracking-wider|tracking-widest)$/,
      variants: ['sm', 'md', 'lg', 'xl'],
    },
    // Border radius, border widths, dividers
    {
      pattern: /^(rounded|rounded-t|rounded-b|rounded-l|rounded-r|rounded-tl|rounded-tr|rounded-bl|rounded-br|border|border-t|border-b|border-l|border-r|divide-x|divide-y)$/,
      variants: ['hover', 'focus', 'sm', 'md', 'lg', 'xl'],
    },
    {
      pattern: /^(rounded)-(none|sm|md|lg|xl|2xl|full)$/,
      variants: ['hover', 'focus'],
    },
    // Effects / transitions / transforms
    {
      pattern: /^(shadow|shadow-sm|shadow-md|shadow-lg|shadow-xl|shadow-2xl|opacity|blur|brightness|contrast|saturate|transition|transition-colors|transition-shadow|duration|ease-in|ease-out|ease-in-out|ease-linear)$/,
      variants: ['hover', 'focus', 'active', 'group-hover'],
    },
    {
      pattern: /^(opacity|duration|delay)-(0|5|10|20|25|50|75|100|150|200|300|500|700|1000)$/,
      variants: ['hover', 'focus', 'group-hover'],
    },
    {
      pattern: /^(-?translate-x|-?translate-y|-?scale|-?rotate)-(0|0\.5|1|2|3|4|5|10|25|50|75|90|100|110|125|150|180|200)$/,
      variants: ['hover', 'focus', 'active', 'group-hover'],
    },
    // Misc utilities commonly used
    {
      pattern: /^(flex|inline-flex|block|inline-block|inline|grid|hidden|table|relative|absolute|sticky|fixed|overflow|overflow-x|overflow-y|truncate|line-clamp|whitespace|align-self|justify-self|object-cover|object-contain|object-center|cursor-pointer|cursor-not-allowed|select-none|pointer-events-none|pointer-events-auto|animate-pulse|animate-bounce|animate-spin|animate-none)$/,
      variants: ['hover', 'focus', 'sm', 'md', 'lg', 'xl'],
    },
    {
      pattern: /^(overflow|overflow-x|overflow-y)-(auto|hidden|visible|scroll|ellipsis|clip)$/,
      variants: ['hover', 'sm', 'md', 'lg', 'xl'],
    },
    {
      pattern: /^(flex)-(1|auto|none|initial|col|row|row-reverse|col-reverse|wrap|nowrap|wrap-reverse)$/,
      variants: ['sm', 'md', 'lg', 'xl'],
    },
    {
      pattern: /^(items|justify|content|self)-(start|end|center|between|around|evenly|baseline|stretch|auto)$/,
      variants: ['sm', 'md', 'lg', 'xl'],
    },
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      colors: {
        primary: {
          50:  '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        sidebar: {
          DEFAULT: '#111827',
          light: '#1f2937',
        },
      },
    },
  },
  plugins: [],
}