document.addEventListener("DOMContentLoaded", function () {
	var header = document.querySelector(".site-header");
	var toggle = document.querySelector(".site-menu-toggle");
	var panel = document.querySelector(".site-header__panel");
	var searchToggle = document.querySelector(".site-search-toggle");
	var searchPanel = document.querySelector(".site-search-panel");
	var revealItems = document.querySelectorAll("[data-reveal]");

	if (header) {
		var syncHeaderState = function () {
			header.classList.toggle("is-scrolled", window.scrollY > 18);
		};

		syncHeaderState();
		window.addEventListener("scroll", syncHeaderState, { passive: true });
	}

	if (header && toggle && panel) {
		var closeMenu = function () {
			header.classList.remove("is-menu-open");
			toggle.setAttribute("aria-expanded", "false");
		};

		toggle.addEventListener("click", function () {
			var isOpen = header.classList.toggle("is-menu-open");
			toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
		});

		panel.querySelectorAll("a").forEach(function (link) {
			link.addEventListener("click", function () {
				if (window.innerWidth <= 1180) {
					closeMenu();
				}
			});
		});

		window.addEventListener("resize", function () {
			if (window.innerWidth > 1180) {
				closeMenu();
			}
		});
	}

	if (searchToggle && searchPanel) {
		var closeSearch = function () {
			searchPanel.hidden = true;
			searchToggle.setAttribute("aria-expanded", "false");
		};

		searchToggle.addEventListener("click", function () {
			var willOpen = searchPanel.hidden;
			searchPanel.hidden = !willOpen;
			searchToggle.setAttribute("aria-expanded", willOpen ? "true" : "false");
		});

		document.addEventListener("click", function (event) {
			if (!searchPanel.hidden && !searchPanel.contains(event.target) && !searchToggle.contains(event.target)) {
				closeSearch();
			}
		});
	}

	document.addEventListener("click", function (event) {
		var logoutLink = event.target.closest('a[href*="customer-logout"]');

		if (!logoutLink) {
			return;
		}

		if (!window.confirm("Bạn có chắc muốn đăng xuất không?")) {
			event.preventDefault();
		}
	});

	document.querySelectorAll("[data-product-gallery]").forEach(function (gallery) {
		var mainImage = gallery.querySelector("[data-product-main-image]");
		var thumbs = gallery.querySelectorAll("[data-product-thumb]");

		if (!mainImage || !thumbs.length) {
			return;
		}

		thumbs.forEach(function (thumb) {
			thumb.addEventListener("click", function () {
				var full = thumb.getAttribute("data-full");
				var srcset = thumb.getAttribute("data-srcset");
				var alt = thumb.getAttribute("data-alt");

				if (full) {
					mainImage.setAttribute("src", full);
				}

				if (srcset) {
					mainImage.setAttribute("srcset", srcset);
				} else {
					mainImage.removeAttribute("srcset");
				}

				if (alt) {
					mainImage.setAttribute("alt", alt);
				}

				thumbs.forEach(function (item) {
					item.classList.remove("is-active");
				});

				thumb.classList.add("is-active");
			});
		});
	});

	if (!revealItems.length) {
		return;
	}

	if (!("IntersectionObserver" in window)) {
		revealItems.forEach(function (item) {
			item.classList.add("is-visible");
		});
		return;
	}

	var observer = new IntersectionObserver(
		function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add("is-visible");
					observer.unobserve(entry.target);
				}
			});
		},
		{
			rootMargin: "0px 0px -80px 0px",
			threshold: 0.12,
		}
	);

	revealItems.forEach(function (item, index) {
		item.style.transitionDelay = Math.min(index * 0.04, 0.22) + "s";
		observer.observe(item);
	});
});
