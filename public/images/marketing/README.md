# Landing page images

Images live in `public/images/` (one level up). The landing page references them
from `resources/views/marketing/home.blade.php`.

| File                         | Used for                 | Aspect |
| ---------------------------- | ------------------------ | ------ |
| `hero-portal.png`            | Hero device frame        | 4:5    |
| `SMS-Hero-Picture-Holder.png`| "How does it work?" card | 16:10  |
| `about-team.jpg`             | About section            | 4:3    |
| `service-campaigns.jpg`      | Services — campaigns     | 16:10  |
| `service-lists.jpg`          | Services — list cleaning | 16:10  |
| `service-billing.jpg`        | Services — invoicing     | 16:10  |

Notes:

- Compress before committing when you can (aim under ~300 KB each).
- Images use `object-cover`, so keep the subject near the centre.
- After changing images on production, rebuild the Docker image
  (`docker compose up -d --build`) so `sms-web` picks them up.
