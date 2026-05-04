# GSAP and ScrollSmoother

See [docs/README.md](README.md) for general theme docs.

This theme bundles **GSAP** from npm (MIT-licensed core). Some features use **ScrollSmoother** and other plugins loaded from the same package structure.

**ScrollSmoother is a GSAP bonus plugin.** Production use requires compliance with [GreenSock’s licensing](https://gsap.com/licensing/) (typically Club GSAP for commercial sites). The theme authors do not grant a GSAP license—ensure your project has the correct entitlement before shipping.

If you remove ScrollSmoother, adjust `resources/scripts/utils/gsap-manager.js` so `ScrollSmoother.create` is not called and update any dependent behaviour.
