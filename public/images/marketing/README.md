# Marketing images

Drop image files here using the exact filenames below and they replace the dashed
placeholders on the landing page automatically — no code change needed. Any file
that is missing keeps rendering its placeholder, so the page never breaks.

| Filename                 | Used for                     | Aspect ratio | Suggested size |
| ------------------------ | ---------------------------- | ------------ | -------------- |
| `hero-portal.jpg`        | Hero device frame            | 4:5          | 900 × 1125     |
| `how-it-works.jpg`       | "How does it work?" card     | 16:10 / tall | 800 × 900      |
| `about-team.jpg`         | About section                | 4:3          | 1200 × 900     |
| `service-campaigns.jpg`  | Services card 1              | 16:10        | 960 × 600      |
| `service-lists.jpg`      | Services card 2              | 16:10        | 960 × 600      |
| `service-billing.jpg`    | Services card 3              | 16:10        | 960 × 600      |

Notes:

- Compress before committing (aim for under ~200 KB each). WebP works too — rename
  the reference in `resources/views/marketing/home.blade.php` if you switch format.
- Images are cropped with `object-cover`, so keep the subject near the centre.
- Alt text lives beside each `<x-marketing.photo>` call in the landing page; update it
  to describe the photo you actually use.
