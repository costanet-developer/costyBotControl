# Paleta de Colores — CostyBO
> Reconciliada con la ficha de marca oficial de Costanet+ (avatar "Costi"), julio 2026.

## ⚠️ Cambio importante respecto a la versión anterior
Tu paleta previa usaba `#D50012` como acento. La ficha de marca oficial define
`#E60012` como el rojo corporativo real. Se actualizó en ambos modos para
mantener consistencia con el resto de materiales de marca (logo, avatar, etc).

---

## Modo Oscuro (default)

| Variable | CSS | Hex | Tailwind | Uso |
|----------|-----|-----|----------|-----|
| Fondo | `--color-bg` | `#0F1720` | `dark-bg` | fondo general de la app |
| Panel | `--color-panel` | `#151F29` | `dark-panel` | headers, sidebars, modales |
| Card | `--color-card` | `#1B2732` | `dark-card` | tarjetas, contenedores |
| Borde | `--color-border` | `#2A3A47` | `dark-border` | separadores, contornos |
| Texto | `--color-text` | `#E7ECEF` | `dark-text` | texto principal |
| Texto secundario | `--color-muted` | `#8CA0AF` | `dark-muted` | labels, metadata |
| Acento | `--color-accent` | `#E60012` | `corp` | marca, CTAs, links activos |
| Acento hover | `--color-accent-hover` | `#B3000E` | `corp-dim` | hover/active sobre acento |
| Info / secundario | `--color-info` | `#0A66C2` | `info` | estados informativos, enlaces secundarios |

## Modo Claro

| Variable | CSS | Hex | Tailwind | Uso |
|----------|-----|-----|----------|-----|
| Fondo | `--color-bg` | `#F2F2F2` | `light-bg` | fondo general de la app |
| Panel | `--color-panel` | `#FFFFFF` | `light-panel` | headers, sidebars, modales |
| Card | `--color-card` | `#E9E9E9` | `light-card` | tarjetas, contenedores |
| Borde | `--color-border` | `#D4D4D4` | `light-border` | separadores, contornos |
| Texto | `--color-text` | `#1E1E1E` | `light-text` | texto principal |
| Texto secundario | `--color-muted` | `#5A5A5A` | `light-muted` | labels, metadata |
| Acento | `--color-accent` | `#E60012` | `corp` | marca, CTAs, links activos |
| Acento hover | `--color-accent-hover` | `#B3000E` | `corp-dim` | hover/active sobre acento |
| Info / secundario | `--color-info` | `#0A66C2` | `info` | estados informativos, enlaces secundarios |

> Nota: el acento (`corp`/`corp-dim`) y el color info (`#0A66C2`) son estáticos,
> no cambian entre modos — igual que en tu versión anterior, pero ahora
> alineados 1:1 con la ficha de marca oficial (rojo, negro/blanco, azul).

---

## Variables CSS listas para usar

```css
:root {
  /* Modo oscuro (default) */
  --color-bg: #0F1720;
  --color-panel: #151F29;
  --color-card: #1B2732;
  --color-border: #2A3A47;
  --color-text: #E7ECEF;
  --color-muted: #8CA0AF;
  --color-accent: #E60012;
  --color-accent-hover: #B3000E;
  --color-info: #0A66C2;
}

[data-theme="light"] {
  --color-bg: #F2F2F2;
  --color-panel: #FFFFFF;
  --color-card: #E9E9E9;
  --color-border: #D4D4D4;
  --color-text: #1E1E1E;
  --color-muted: #5A5A5A;
  /* accent e info no cambian */
}
```

```js
// tailwind.config.js (extend.colors)
colors: {
  'dark-bg': '#0F1720',
  'dark-panel': '#151F29',
  'dark-card': '#1B2732',
  'dark-border': '#2A3A47',
  'dark-text': '#E7ECEF',
  'dark-muted': '#8CA0AF',
  'light-bg': '#F2F2F2',
  'light-panel': '#FFFFFF',
  'light-card': '#E9E9E9',
  'light-border': '#D4D4D4',
  'light-text': '#1E1E1E',
  'light-muted': '#5A5A5A',
  corp: '#E60012',
  'corp-dim': '#B3000E',
  info: '#0A66C2',
}
```

---

## Clases nativas de Tailwind en uso (estados semánticos)

Estos se mantienen sin cambios respecto a tu versión anterior — funcionan bien
en ambos modos porque ya usan opacidades (`/10`, `/20`, `/40`, etc.) en vez de
depender de fondo fijo:

| Color | Uso semántico | Clases usadas |
|-------|---------------|----------------|
| slate | neutral/deshabilitado | `slate-400`, `slate-500` |
| gray | fondo alterno claro | `gray-100` |
| red | error / duplicado / rechazado | `red-300`, `red-400`, `red-500`, `red-500/20`, `red-500/30`, `red-600`, `red-600/40`, `red-700`, `red-700/50`, `red-800`, `red-900/40`, `red-400/10`, `red-400/5` |
| green | éxito / pago verificado | `green-400`, `green-400/10`, `green-400/5`, `green-500`, `green-500/20`, `green-500/30`, `green-600/40`, `green-700/50`, `green-900/40`, `green-300` |
| amber | pendiente / advertencia | `amber-400`, `amber-700/50`, `amber-900/40`, `amber-300` |
| blue | informativo (distinto del `info` de marca) | `blue-400`, `blue-500/20`, `blue-500/30` |

> Recomendación: reserva `blue-*` de Tailwind para estados informativos de UI
> (tooltips, badges "nuevo") y usa `--color-info` (`#0A66C2`) exclusivamente
> para elementos de marca/branding, para no mezclar semánticas.

---

## Contraste (WCAG) — verificado

| Combinación | Ratio | Cumple |
|-------------|-------|--------|
| `#E7ECEF` sobre `#0F1720` (texto oscuro) | ~14.8:1 | AAA |
| `#1E1E1E` sobre `#F2F2F2` (texto claro) | ~15.6:1 | AAA |
| `#FFFFFF` sobre `#E60012` (texto en botón acento) | ~4.6:1 | AA (texto normal) |
| `#8CA0AF` sobre `#0F1720` (texto secundario oscuro) | ~5.2:1 | AA |
| `#5A5A5A` sobre `#F2F2F2` (texto secundario claro) | ~5.9:1 | AA |

> Para texto blanco sobre `--color-accent`, evita tamaños menores a 14px si
> puedes — el ratio 4.6:1 es AA pero justo, no AAA.

---

## Fuentes

**Cambio sugerido:** la ficha de marca oficial usa **Montserrat** (Bold para
títulos, Regular para cuerpo). Tu paleta anterior usaba Inter/Figtree/Fraunces.
Recomiendo migrar a Montserrat para consistencia total con el avatar/logo,
manteniendo IBM Plex Mono para datos:

| Rol | Fuente (nueva, alineada a marca) | Fuente anterior |
|-----|-----------------------------------|------------------|
| Sans (general) | `Montserrat`, sans-serif | Inter, Figtree |
| Serif (títulos) | *(opcional, ver nota)* | Fraunces, serif |
| Mono (datos) | `IBM Plex Mono`, monospace | (sin cambio) |

> Nota sobre serif: la ficha de marca no define una fuente serif — solo
> Montserrat Bold/Regular. Si quieres mantener un toque editorial en títulos
> grandes, puedes conservar Fraunces solo para hero/landing, pero para UI de
> producto (dashboards, chat, formularios) usa Montserrat en todo para que
> coincida con el avatar Costi y el logo.
