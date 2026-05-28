const ready = (callback) => {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", callback, { once: true });
  } else {
    callback();
  }
};

ready(() => {
  const refreshIcons = () => {
    if (window.lucide) {
      window.lucide.createIcons();
    }
  };

  refreshIcons();

  const header = document.querySelector("[data-header]");
  const navToggle = document.querySelector("[data-nav-toggle]");
  const navMenu = document.querySelector("[data-nav-menu]");
  const navLinks = Array.from(document.querySelectorAll(".nav-menu a"));
  const finderForm = document.querySelector("[data-finder-form]");
  const finderNote = document.querySelector("[data-finder-note]");
  const filterButtons = Array.from(document.querySelectorAll("[data-filter]"));
  const destinationCards = Array.from(document.querySelectorAll("[data-group]"));
  const mapPins = Array.from(document.querySelectorAll("[data-map-target]"));
  const mapRegion = document.querySelector("[data-map-region]");
  const mapTitle = document.querySelector("[data-map-title]");
  const mapCopy = document.querySelector("[data-map-copy]");
  const mapMeta = document.querySelector("[data-map-meta]");
  const testimonialTrack = document.querySelector("[data-testimonial-track]");
  const testimonials = testimonialTrack ? Array.from(testimonialTrack.querySelectorAll(".testimonial")) : [];
  const prevTestimonial = document.querySelector("[data-testimonial-prev]");
  const nextTestimonial = document.querySelector("[data-testimonial-next]");
  const consultForm = document.querySelector("[data-consult-form]");
  const formStatus = document.querySelector("[data-form-status]");

  const setHeaderState = () => {
    if (!header) return;
    header.classList.toggle("is-scrolled", window.scrollY > 12);
  };

  setHeaderState();
  window.addEventListener("scroll", setHeaderState, { passive: true });

  const closeNavigation = () => {
    document.body.classList.remove("nav-open");
    if (navToggle) {
      navToggle.setAttribute("aria-expanded", "false");
      navToggle.setAttribute("aria-label", "Open navigation");
      navToggle.innerHTML = '<i data-lucide="menu"></i>';
      refreshIcons();
    }
  };

  const toggleNavigation = () => {
    if (!navToggle) return;
    const isOpen = document.body.classList.toggle("nav-open");
    navToggle.setAttribute("aria-expanded", String(isOpen));
    navToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
    navToggle.innerHTML = isOpen ? '<i data-lucide="x"></i>' : '<i data-lucide="menu"></i>';
    refreshIcons();
  };

  if (navToggle && navMenu) {
    let lastTouchToggle = 0;

    navToggle.addEventListener("click", () => {
      if (Date.now() - lastTouchToggle < 500) return;
      toggleNavigation();
    });

    navToggle.addEventListener("touchend", (event) => {
      event.preventDefault();
      if (Date.now() - lastTouchToggle < 500) return;
      lastTouchToggle = Date.now();
      toggleNavigation();
    }, { passive: false });

    navToggle.addEventListener("pointerup", (event) => {
      if (event.pointerType !== "touch") return;
      if (Date.now() - lastTouchToggle < 500) return;
      lastTouchToggle = Date.now();
      toggleNavigation();
    });

    navMenu.addEventListener("click", (event) => {
      if (event.target.closest("a")) {
        closeNavigation();
      }
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && document.body.classList.contains("nav-open")) {
      closeNavigation();
    }
  });

  const dropdownItems = Array.from(document.querySelectorAll("[data-dropdown]"));
  const isDesktopDropdown = () => window.matchMedia("(min-width: 961px)").matches;

  const closeAllDropdowns = (except) => {
    dropdownItems.forEach((item) => {
      if (item === except) return;
      if (!item.classList.contains("is-open")) return;
      item.classList.remove("is-open");
      const trigger = item.querySelector("[data-dropdown-trigger]");
      if (trigger) trigger.setAttribute("aria-expanded", "false");
    });
  };

  const setDropdown = (item, open) => {
    item.classList.toggle("is-open", open);
    const trigger = item.querySelector("[data-dropdown-trigger]");
    if (trigger) trigger.setAttribute("aria-expanded", String(open));
    if (open) closeAllDropdowns(item);
  };

  dropdownItems.forEach((item) => {
    const trigger = item.querySelector("[data-dropdown-trigger]");
    const panel = item.querySelector("[data-dropdown-panel]");
    if (!trigger || !panel) return;

    let hoverTimer = null;
    const openWithDelay = () => {
      if (!isDesktopDropdown()) return;
      window.clearTimeout(hoverTimer);
      setDropdown(item, true);
    };
    const closeWithDelay = () => {
      if (!isDesktopDropdown()) return;
      window.clearTimeout(hoverTimer);
      hoverTimer = window.setTimeout(() => setDropdown(item, false), 140);
    };

    item.addEventListener("mouseenter", openWithDelay);
    item.addEventListener("mouseleave", closeWithDelay);
    panel.addEventListener("mouseenter", () => window.clearTimeout(hoverTimer));

    trigger.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      setDropdown(item, !item.classList.contains("is-open"));
    });

    trigger.addEventListener("keydown", (event) => {
      if (event.key === "ArrowDown" || event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        setDropdown(item, true);
        const firstLink = panel.querySelector("a");
        if (firstLink) firstLink.focus();
      }
    });

    panel.addEventListener("click", (event) => {
      if (event.target.closest("a")) {
        setDropdown(item, false);
        if (document.body.classList.contains("nav-open")) closeNavigation();
      }
    });
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest("[data-dropdown]")) closeAllDropdowns();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeAllDropdowns();
  });

  window.addEventListener("resize", () => closeAllDropdowns(), { passive: true });

  const mbbsCard = document.querySelector("[data-mbbs-card]");
  if (mbbsCard) {
    const mbbsPanel = mbbsCard.querySelector("#mbbs-country-panel");
    const mbbsClose = mbbsCard.querySelector("[data-mbbs-close]");

    const setMbbsRoutes = (open) => {
      mbbsCard.classList.toggle("is-routes-open", open);
      mbbsCard.setAttribute("aria-expanded", String(open));
      if (mbbsPanel) mbbsPanel.setAttribute("aria-hidden", String(!open));
    };

    mbbsCard.addEventListener("click", (event) => {
      if (event.target.closest("a")) return;
      if (event.target.closest("[data-mbbs-close]")) {
        event.stopPropagation();
        setMbbsRoutes(false);
        return;
      }
      if (event.target.closest(".mbbs-routes-panel")) return;
      setMbbsRoutes(!mbbsCard.classList.contains("is-routes-open"));
    });

    mbbsCard.addEventListener("keydown", (event) => {
      if (event.target !== mbbsCard) return;
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      setMbbsRoutes(!mbbsCard.classList.contains("is-routes-open"));
    });

    if (mbbsClose) {
      mbbsClose.addEventListener("click", (event) => {
        event.stopPropagation();
        setMbbsRoutes(false);
        mbbsCard.focus();
      });
    }

    document.addEventListener("click", (event) => {
      if (!mbbsCard.contains(event.target)) setMbbsRoutes(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") setMbbsRoutes(false);
    });
  }

  const codepenMaps = Array.from(document.querySelectorAll("[data-codepen-map] svg"));
  codepenMaps.forEach((svg) => {
    const setRandomClass = () => {
      const items = Array.from(svg.querySelectorAll("circle"));
      const number = items.length;
      if (!number) return;
      const random = Math.floor(Math.random() * number);
      items.forEach((item) => item.classList.remove("banaan"));
      items[random].classList.add("banaan");
    };

    setRandomClass();
    window.setInterval(setRandomClass, 2000);
  });

  const revealItems = Array.from(document.querySelectorAll(".reveal"));
  const revealVisibleItems = () => {
    revealItems.forEach((item) => {
      if (item.classList.contains("is-visible")) return;
      if (item.getBoundingClientRect().top < window.innerHeight * 0.94) {
        item.classList.add("is-visible");
      }
    });
  };

  if ("IntersectionObserver" in window) {
    const revealObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.12 }
    );

    revealItems.forEach((item) => revealObserver.observe(item));
    revealVisibleItems();
    window.addEventListener("scroll", revealVisibleItems, { passive: true });
    window.addEventListener("resize", revealVisibleItems);
    window.setTimeout(revealVisibleItems, 650);
    const revealTimer = window.setInterval(() => {
      revealVisibleItems();
      if (revealItems.every((item) => item.classList.contains("is-visible"))) {
        window.clearInterval(revealTimer);
      }
    }, 450);
  } else {
    revealItems.forEach((item) => item.classList.add("is-visible"));
  }

  const sections = navLinks
    .map((link) => {
      const href = link.getAttribute("href") || "";
      if (!href.startsWith("#") || href.length < 2) return null;
      let target = null;
      try { target = document.querySelector(href); } catch (_) { return null; }
      return target ? { link, target } : null;
    })
    .filter(Boolean);

  if ("IntersectionObserver" in window && sections.length) {
    const activeObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          navLinks.forEach((link) => link.classList.remove("is-active"));
          const active = sections.find((section) => section.target === entry.target);
          if (active) active.link.classList.add("is-active");
        });
      },
      { rootMargin: "-35% 0px -55% 0px", threshold: 0.02 }
    );

    sections.forEach(({ target }) => activeObserver.observe(target));
  }

  if (finderForm && finderNote) {
    finderForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const data = new FormData(finderForm);
      const destination = data.get("destination");
      const program = data.get("program");
      const intake = data.get("intake");
      finderNote.textContent = `${program} planning for ${destination} in ${intake}: start with profile diagnostics, eligibility checks, and a three-tier university list.`;
    });
  }

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const filter = button.dataset.filter;

      filterButtons.forEach((item) => item.classList.remove("is-active"));
      button.classList.add("is-active");

      destinationCards.forEach((card) => {
        const groups = card.dataset.group.split(" ");
        card.classList.toggle("is-hidden", filter !== "all" && !groups.includes(filter));
      });
    });
  });

  const destinationInsights = {
    uk: {
      region: "Europe",
      title: "United Kingdom",
      copy: "Focused degrees, one-year masters, strong global recognition, and city-led career exploration.",
      meta: "Best for: focused programs, brand recognition, faster postgraduate timelines"
    },
    canada: {
      region: "North America",
      title: "Canada",
      copy: "Academic quality, co-op pathways, and a student experience that rewards early planning around costs and eligibility.",
      meta: "Best for: settlement pathways, co-op options, balanced budgets"
    },
    usa: {
      region: "North America",
      title: "United States",
      copy: "Deep specialization, research access, and broad campus choice for students with a strong application narrative.",
      meta: "Best for: research, scholarships, flexible academic exploration"
    },
    germany: {
      region: "Europe",
      title: "Germany",
      copy: "Value-led public education with strong technical pathways when language, prerequisites, and timing are handled early.",
      meta: "Best for: engineering, value-led study, structured documentation"
    },
    ireland: {
      region: "Europe",
      title: "Ireland",
      copy: "A compact market with strong technology, business, healthcare, and applied postgraduate opportunities.",
      meta: "Best for: industry access, applied masters, practical city networks"
    },
    australia: {
      region: "Oceania",
      title: "Australia",
      copy: "Practical learning, flexible intakes, vibrant cities, and strong student support for career-aligned programs.",
      meta: "Best for: flexible intakes, applied learning, student lifestyle"
    }
  };

  const setMapInsight = (target) => {
    const insight = destinationInsights[target];
    if (!insight || !mapRegion || !mapTitle || !mapCopy || !mapMeta) return;
    mapPins.forEach((pin) => pin.classList.toggle("is-active", pin.dataset.mapTarget === target));
    mapRegion.textContent = insight.region;
    mapTitle.textContent = insight.title;
    mapCopy.textContent = insight.copy;
    mapMeta.textContent = insight.meta;
  };

  mapPins.forEach((pin) => {
    pin.addEventListener("click", () => setMapInsight(pin.dataset.mapTarget));
    pin.addEventListener("mouseenter", () => setMapInsight(pin.dataset.mapTarget));
  });

  let testimonialIndex = 0;
  const showTestimonial = (index) => {
    if (!testimonials.length) return;
    testimonialIndex = (index + testimonials.length) % testimonials.length;
    testimonials.forEach((testimonial, itemIndex) => {
      testimonial.classList.toggle("is-active", itemIndex === testimonialIndex);
    });
  };

  if (testimonials.length) {
    prevTestimonial?.addEventListener("click", () => showTestimonial(testimonialIndex - 1));
    nextTestimonial?.addEventListener("click", () => showTestimonial(testimonialIndex + 1));

    let autoAdvance = window.setInterval(() => showTestimonial(testimonialIndex + 1), 6800);
    testimonialTrack.addEventListener("mouseenter", () => window.clearInterval(autoAdvance));
    testimonialTrack.addEventListener("mouseleave", () => {
      autoAdvance = window.setInterval(() => showTestimonial(testimonialIndex + 1), 6800);
    });
  }

  if (consultForm && formStatus) {
    consultForm.addEventListener("submit", (event) => {
      event.preventDefault();

      if (!consultForm.checkValidity()) {
        consultForm.reportValidity();
        return;
      }

      const formData = new FormData(consultForm);
      const name = String(formData.get("name") || "there").trim().split(" ")[0];
      formStatus.textContent = `Thank you, ${name}. Your enquiry is ready for the OneDegreeAdvisory team.`;
      consultForm.reset();
    });
  }

  const FALLBACK_CITIES_BY_STATE = {
    "Andhra Pradesh": ["Visakhapatnam", "Vijayawada", "Guntur", "Nellore", "Kurnool", "Tirupati", "Rajahmundry", "Kakinada", "Anantapur", "Chittoor", "Kadapa", "Eluru", "Ongole"],
    "Arunachal Pradesh": ["Itanagar", "Naharlagun", "Pasighat", "Bomdila", "Tawang", "Ziro", "Tezu"],
    "Assam": ["Guwahati", "Dibrugarh", "Silchar", "Jorhat", "Nagaon", "Tinsukia", "Tezpur", "Bongaigaon", "Karimganj", "Dhubri"],
    "Bihar": ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur", "Darbhanga", "Purnia", "Bihar Sharif", "Arrah", "Begusarai", "Katihar", "Munger", "Chhapra"],
    "Chhattisgarh": ["Raipur", "Bhilai", "Bilaspur", "Korba", "Durg", "Rajnandgaon", "Jagdalpur", "Ambikapur", "Raigarh"],
    "Goa": ["Panaji", "Margao", "Vasco da Gama", "Mapusa", "Ponda"],
    "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar", "Junagadh", "Gandhinagar", "Anand", "Navsari", "Mehsana", "Bharuch", "Gandhidham"],
    "Haryana": ["Faridabad", "Gurugram", "Panipat", "Ambala", "Yamunanagar", "Rohtak", "Hisar", "Karnal", "Sonipat", "Panchkula", "Bhiwani", "Sirsa", "Rewari"],
    "Himachal Pradesh": ["Shimla", "Dharamshala", "Solan", "Mandi", "Kullu", "Manali", "Hamirpur", "Una", "Bilaspur", "Chamba", "Kangra", "Palampur"],
    "Jharkhand": ["Ranchi", "Jamshedpur", "Dhanbad", "Bokaro", "Hazaribagh", "Deoghar", "Phusro", "Giridih", "Ramgarh"],
    "Karnataka": ["Bengaluru", "Mysuru", "Hubballi-Dharwad", "Mangaluru", "Belagavi", "Kalaburagi", "Davanagere", "Ballari", "Vijayapura", "Shivamogga", "Tumakuru", "Udupi", "Hassan"],
    "Kerala": ["Thiruvananthapuram", "Kochi", "Kozhikode", "Thrissur", "Kollam", "Kannur", "Alappuzha", "Palakkad", "Malappuram", "Kottayam", "Pathanamthitta"],
    "Madhya Pradesh": ["Indore", "Bhopal", "Jabalpur", "Gwalior", "Ujjain", "Sagar", "Dewas", "Satna", "Ratlam", "Rewa", "Vidisha", "Khandwa", "Burhanpur"],
    "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Nashik", "Thane", "Chhatrapati Sambhajinagar", "Solapur", "Kolhapur", "Amravati", "Navi Mumbai", "Vasai-Virar", "Sangli", "Ahmednagar", "Latur", "Jalgaon", "Akola"],
    "Manipur": ["Imphal", "Thoubal", "Bishnupur", "Churachandpur", "Ukhrul", "Senapati"],
    "Meghalaya": ["Shillong", "Tura", "Jowai", "Nongpoh", "Williamnagar"],
    "Mizoram": ["Aizawl", "Lunglei", "Saiha", "Champhai", "Kolasib", "Serchhip"],
    "Nagaland": ["Kohima", "Dimapur", "Mokokchung", "Tuensang", "Wokha", "Zunheboto"],
    "Odisha": ["Bhubaneswar", "Cuttack", "Rourkela", "Berhampur", "Sambalpur", "Puri", "Balasore", "Bhadrak", "Jeypore", "Angul"],
    "Punjab": ["Ludhiana", "Amritsar", "Jalandhar", "Patiala", "Bathinda", "Mohali", "Pathankot", "Hoshiarpur", "Moga", "Firozpur", "Kapurthala"],
    "Rajasthan": ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Ajmer", "Bikaner", "Alwar", "Bharatpur", "Sikar", "Pali", "Sri Ganganagar", "Kishangarh", "Beawar"],
    "Sikkim": ["Gangtok", "Namchi", "Gyalshing", "Mangan", "Rangpo"],
    "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Tiruchirappalli", "Salem", "Tirunelveli", "Tiruppur", "Erode", "Vellore", "Thoothukudi", "Dindigul", "Thanjavur", "Hosur", "Nagercoil"],
    "Telangana": ["Hyderabad", "Warangal", "Nizamabad", "Karimnagar", "Khammam", "Mahbubnagar", "Ramagundam", "Secunderabad", "Adilabad", "Suryapet"],
    "Tripura": ["Agartala", "Udaipur", "Dharmanagar", "Kailashahar", "Belonia", "Khowai"],
    "Uttar Pradesh": ["Lucknow", "Kanpur", "Ghaziabad", "Agra", "Varanasi", "Meerut", "Prayagraj", "Bareilly", "Aligarh", "Moradabad", "Saharanpur", "Gorakhpur", "Noida", "Firozabad", "Jhansi", "Mathura", "Ayodhya"],
    "Uttarakhand": ["Dehradun", "Haridwar", "Rishikesh", "Nainital", "Roorkee", "Haldwani", "Rudrapur", "Kashipur", "Mussoorie"],
    "West Bengal": ["Kolkata", "Howrah", "Asansol", "Siliguri", "Durgapur", "Bardhaman", "Malda", "Kharagpur", "Haldia", "Berhampore"],
    "Andaman and Nicobar Islands": ["Port Blair", "Diglipur", "Mayabunder", "Rangat", "Car Nicobar"],
    "Chandigarh": ["Chandigarh"],
    "Dadra and Nagar Haveli and Daman and Diu": ["Silvassa", "Daman", "Diu"],
    "Delhi": ["New Delhi", "North Delhi", "South Delhi", "East Delhi", "West Delhi", "Central Delhi", "Dwarka", "Rohini", "Saket", "Karol Bagh", "Pitampura"],
    "Jammu and Kashmir": ["Srinagar", "Jammu", "Anantnag", "Baramulla", "Udhampur", "Kathua", "Sopore", "Pulwama"],
    "Ladakh": ["Leh", "Kargil"],
    "Lakshadweep": ["Kavaratti", "Agatti", "Andrott", "Minicoy", "Amini"],
    "Puducherry": ["Puducherry", "Karaikal", "Mahe", "Yanam"]
  };

  const STATES_API_URL = "https://countriesnow.space/api/v0.1/countries/states";
  const CITIES_API_URL = "https://countriesnow.space/api/v0.1/countries/state/cities";
  const STATE_ALIASES = {
    "Dadra and Nagar Haveli and Daman and Diu": ["Dadra And Nagar Haveli", "Dadra and Nagar Haveli", "Daman and Diu", "Daman And Diu"],
    "Chhatrapati Sambhajinagar": ["Aurangabad"]
  };
  const CACHE_PREFIX = "oda_in_v1_";
  const CACHE_TTL_MS = 30 * 24 * 60 * 60 * 1000;
  const API_TIMEOUT_MS = 6000;

  const readCache = (key) => {
    try {
      const raw = localStorage.getItem(CACHE_PREFIX + key);
      if (!raw) return null;
      const obj = JSON.parse(raw);
      if (!obj || typeof obj.ts !== "number" || !obj.data) return null;
      if (Date.now() - obj.ts > CACHE_TTL_MS) return null;
      return obj.data;
    } catch (_) { return null; }
  };

  const writeCache = (key, data) => {
    try {
      localStorage.setItem(CACHE_PREFIX + key, JSON.stringify({ ts: Date.now(), data }));
    } catch (_) {}
  };

  const fetchJson = async (url, body) => {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), API_TIMEOUT_MS);
    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify(body),
        signal: controller.signal
      });
      if (!res.ok) throw new Error("HTTP " + res.status);
      const json = await res.json();
      if (json.error) throw new Error("API error");
      return json.data;
    } finally {
      clearTimeout(timer);
    }
  };

  const fetchStatesFromApi = async () => {
    const cached = readCache("states");
    if (cached) return cached;
    const data = await fetchJson(STATES_API_URL, { country: "India" });
    if (!data || !Array.isArray(data.states)) throw new Error("Bad states response");
    const states = data.states.map((s) => s.name).filter(Boolean);
    writeCache("states", states);
    return states;
  };

  const fetchCitiesFromApi = async (state) => {
    const cacheKey = "cities_" + state;
    const cached = readCache(cacheKey);
    if (cached) return cached;
    const aliases = [state, ...(STATE_ALIASES[state] || [])];
    let combined = [];
    let anySuccess = false;
    for (const alias of aliases) {
      try {
        const cities = await fetchJson(CITIES_API_URL, { country: "India", state: alias });
        if (Array.isArray(cities) && cities.length) {
          combined = combined.concat(cities);
          anySuccess = true;
        }
      } catch (_) {}
    }
    if (!anySuccess) throw new Error("No cities from API");
    const unique = Array.from(new Set(combined.filter(Boolean)));
    writeCache(cacheKey, unique);
    return unique;
  };

  // ---------- Country data (bundled fallback; restcountries.com enriches at runtime) ----------
  // [name, ISO2, dial, mobile-digit-length]
  const COUNTRIES_FALLBACK = [
    ["Afghanistan","AF","+93",9],["Albania","AL","+355",9],["Algeria","DZ","+213",9],
    ["Andorra","AD","+376",6],["Angola","AO","+244",9],["Argentina","AR","+54",10],
    ["Armenia","AM","+374",8],["Australia","AU","+61",9],["Austria","AT","+43",11],
    ["Azerbaijan","AZ","+994",9],["Bahamas","BS","+1",10],["Bahrain","BH","+973",8],
    ["Bangladesh","BD","+880",10],["Barbados","BB","+1",10],["Belarus","BY","+375",9],
    ["Belgium","BE","+32",9],["Belize","BZ","+501",7],["Benin","BJ","+229",8],
    ["Bhutan","BT","+975",8],["Bolivia","BO","+591",8],["Bosnia and Herzegovina","BA","+387",8],
    ["Botswana","BW","+267",8],["Brazil","BR","+55",11],["Brunei","BN","+673",7],
    ["Bulgaria","BG","+359",9],["Burkina Faso","BF","+226",8],["Burundi","BI","+257",8],
    ["Cambodia","KH","+855",9],["Cameroon","CM","+237",9],["Canada","CA","+1",10],
    ["Cape Verde","CV","+238",7],["Central African Republic","CF","+236",8],["Chad","TD","+235",8],
    ["Chile","CL","+56",9],["China","CN","+86",11],["Colombia","CO","+57",10],
    ["Comoros","KM","+269",7],["Congo","CG","+242",9],["Costa Rica","CR","+506",8],
    ["Croatia","HR","+385",9],["Cuba","CU","+53",8],["Cyprus","CY","+357",8],
    ["Czech Republic","CZ","+420",9],["DR Congo","CD","+243",9],["Denmark","DK","+45",8],
    ["Djibouti","DJ","+253",8],["Dominica","DM","+1",10],["Dominican Republic","DO","+1",10],
    ["Ecuador","EC","+593",9],["Egypt","EG","+20",10],["El Salvador","SV","+503",8],
    ["Equatorial Guinea","GQ","+240",9],["Eritrea","ER","+291",7],["Estonia","EE","+372",8],
    ["Eswatini","SZ","+268",8],["Ethiopia","ET","+251",9],["Fiji","FJ","+679",7],
    ["Finland","FI","+358",10],["France","FR","+33",9],["Gabon","GA","+241",8],
    ["Gambia","GM","+220",7],["Georgia","GE","+995",9],["Germany","DE","+49",11],
    ["Ghana","GH","+233",9],["Greece","GR","+30",10],["Grenada","GD","+1",10],
    ["Guatemala","GT","+502",8],["Guinea","GN","+224",9],["Guinea-Bissau","GW","+245",7],
    ["Guyana","GY","+592",7],["Haiti","HT","+509",8],["Honduras","HN","+504",8],
    ["Hong Kong","HK","+852",8],["Hungary","HU","+36",9],["Iceland","IS","+354",7],
    ["India","IN","+91",10],["Indonesia","ID","+62",11],["Iran","IR","+98",10],
    ["Iraq","IQ","+964",10],["Ireland","IE","+353",9],["Israel","IL","+972",9],
    ["Italy","IT","+39",10],["Jamaica","JM","+1",10],["Japan","JP","+81",10],
    ["Jordan","JO","+962",9],["Kazakhstan","KZ","+7",10],["Kenya","KE","+254",9],
    ["Kuwait","KW","+965",8],["Kyrgyzstan","KG","+996",9],["Laos","LA","+856",10],
    ["Latvia","LV","+371",8],["Lebanon","LB","+961",8],["Lesotho","LS","+266",8],
    ["Liberia","LR","+231",8],["Libya","LY","+218",9],["Liechtenstein","LI","+423",7],
    ["Lithuania","LT","+370",8],["Luxembourg","LU","+352",9],["Macau","MO","+853",8],
    ["Madagascar","MG","+261",9],["Malawi","MW","+265",9],["Malaysia","MY","+60",10],
    ["Maldives","MV","+960",7],["Mali","ML","+223",8],["Malta","MT","+356",8],
    ["Mauritania","MR","+222",8],["Mauritius","MU","+230",8],["Mexico","MX","+52",10],
    ["Moldova","MD","+373",8],["Monaco","MC","+377",8],["Mongolia","MN","+976",8],
    ["Montenegro","ME","+382",8],["Morocco","MA","+212",9],["Mozambique","MZ","+258",9],
    ["Myanmar","MM","+95",10],["Namibia","NA","+264",9],["Nepal","NP","+977",10],
    ["Netherlands","NL","+31",9],["New Zealand","NZ","+64",9],["Nicaragua","NI","+505",8],
    ["Niger","NE","+227",8],["Nigeria","NG","+234",10],["North Macedonia","MK","+389",8],
    ["Norway","NO","+47",8],["Oman","OM","+968",8],["Pakistan","PK","+92",10],
    ["Palestine","PS","+970",9],["Panama","PA","+507",8],["Papua New Guinea","PG","+675",8],
    ["Paraguay","PY","+595",9],["Peru","PE","+51",9],["Philippines","PH","+63",10],
    ["Poland","PL","+48",9],["Portugal","PT","+351",9],["Qatar","QA","+974",8],
    ["Romania","RO","+40",9],["Russia","RU","+7",10],["Rwanda","RW","+250",9],
    ["Saudi Arabia","SA","+966",9],["Senegal","SN","+221",9],["Serbia","RS","+381",9],
    ["Seychelles","SC","+248",7],["Sierra Leone","SL","+232",8],["Singapore","SG","+65",8],
    ["Slovakia","SK","+421",9],["Slovenia","SI","+386",8],["Somalia","SO","+252",8],
    ["South Africa","ZA","+27",9],["South Korea","KR","+82",10],["South Sudan","SS","+211",9],
    ["Spain","ES","+34",9],["Sri Lanka","LK","+94",9],["Sudan","SD","+249",9],
    ["Suriname","SR","+597",7],["Sweden","SE","+46",9],["Switzerland","CH","+41",9],
    ["Syria","SY","+963",9],["Taiwan","TW","+886",9],["Tajikistan","TJ","+992",9],
    ["Tanzania","TZ","+255",9],["Thailand","TH","+66",9],["Timor-Leste","TL","+670",8],
    ["Togo","TG","+228",8],["Trinidad and Tobago","TT","+1",10],["Tunisia","TN","+216",8],
    ["Turkey","TR","+90",10],["Turkmenistan","TM","+993",8],["Uganda","UG","+256",9],
    ["Ukraine","UA","+380",9],["United Arab Emirates","AE","+971",9],["United Kingdom","GB","+44",10],
    ["United States","US","+1",10],["Uruguay","UY","+598",8],["Uzbekistan","UZ","+998",9],
    ["Vanuatu","VU","+678",7],["Venezuela","VE","+58",10],["Vietnam","VN","+84",10],
    ["Yemen","YE","+967",9],["Zambia","ZM","+260",9],["Zimbabwe","ZW","+263",9]
  ];

  const COUNTRIES_API_URL = "https://restcountries.com/v3.1/all?fields=name,cca2,idd";
  const flagUrl = (iso) => `https://flagcdn.com/w40/${iso.toLowerCase()}.png`;
  const flagUrl2x = (iso) => `https://flagcdn.com/w80/${iso.toLowerCase()}.png`;

  const buildCountryItems = (rows) =>
    rows
      .map(([name, iso, dial, length]) => ({
        value: iso,
        name,
        dial,
        length,
        search: (name + " " + dial + " " + iso).toLowerCase()
      }))
      .sort((a, b) => a.name.localeCompare(b.name));

  const fetchCountriesFromApi = async () => {
    const cached = readCache("countries");
    if (cached) return cached;
    try {
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), API_TIMEOUT_MS);
      const res = await fetch(COUNTRIES_API_URL, { signal: controller.signal });
      clearTimeout(timer);
      if (!res.ok) throw new Error();
      const data = await res.json();
      const lengthMap = new Map(COUNTRIES_FALLBACK.map(([, iso, , l]) => [iso, l]));
      const rows = data
        .filter((c) => c.idd && c.idd.root && c.cca2 && c.name && c.name.common)
        .map((c) => {
          const dial = c.idd.root + ((c.idd.suffixes && c.idd.suffixes[0]) || "");
          const length = lengthMap.get(c.cca2) || 10;
          return [c.name.common, c.cca2, dial, length];
        });
      writeCache("countries", rows);
      return rows;
    } catch (_) {
      return null;
    }
  };

  // ---------- Reusable searchable combobox ----------
  const createCombobox = ({ host, items, value, placeholder, onChange, searchPlaceholder, formatItem, formatSelected, emptyText, disabled = false, onDisabledClick }) => {
    const wrap = document.createElement("div");
    wrap.className = "cbx";

    const field = document.createElement("div");
    const listId = "cbx-list-" + Math.random().toString(36).slice(2);
    field.className = "cbx-field";
    field.tabIndex = 0;
    field.setAttribute("role", "combobox");
    field.setAttribute("aria-haspopup", "listbox");
    field.setAttribute("aria-expanded", "false");
    field.setAttribute("aria-controls", listId);

    const valueDisplay = document.createElement("div");
    valueDisplay.className = "cbx-value";

    const panel = document.createElement("div");
    panel.className = "cbx-panel";

    const searchInput = document.createElement("input");
    searchInput.type = "text";
    searchInput.className = "cbx-input";
    searchInput.placeholder = searchPlaceholder || "Search...";
    searchInput.autocomplete = "off";
    searchInput.spellcheck = false;
    searchInput.tabIndex = -1;
    searchInput.setAttribute("aria-label", searchPlaceholder || placeholder || "Search");

    const chevron = document.createElement("i");
    chevron.className = "cbx-chevron";
    chevron.setAttribute("data-lucide", "chevron-down");

    const list = document.createElement("ul");
    list.className = "cbx-list";
    list.setAttribute("role", "listbox");
    list.id = listId;

    field.appendChild(valueDisplay);
    field.appendChild(searchInput);
    field.appendChild(chevron);
    panel.appendChild(list);
    wrap.appendChild(field);
    wrap.appendChild(panel);
    host.appendChild(wrap);

    const state = {
      items: items || [],
      value: value || null,
      filter: "",
      activeIndex: -1,
      filtered: [],
      disabled: !!disabled
    };

    const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

    const defaultFormatItem = (it) => `<span class="cbx-name">${escapeHtml(it.name || it.label || it.value)}</span>`;
    const defaultFormatSelected = (it) => `<span class="cbx-name">${escapeHtml(it.name || it.label || it.value)}</span>`;

    const renderField = () => {
      const sel = state.items.find((x) => x.value === state.value);
      if (sel) {
        valueDisplay.innerHTML = (formatSelected || defaultFormatSelected)(sel);
      } else {
        valueDisplay.innerHTML = `<span class="cbx-placeholder">${escapeHtml(placeholder || "")}</span>`;
      }
      if (window.lucide) window.lucide.createIcons({ icons: window.lucide.icons });
    };

    const renderList = () => {
      const f = state.filter.toLowerCase().trim();
      state.filtered = f ? state.items.filter((it) => (it.search || (it.name || "")).toLowerCase().includes(f)) : state.items;
      list.innerHTML = "";
      if (state.filtered.length === 0) {
        const empty = document.createElement("li");
        empty.className = "cbx-empty";
        empty.textContent = emptyText || "No matches";
        list.appendChild(empty);
        return;
      }
      state.filtered.forEach((it, idx) => {
        const li = document.createElement("li");
        li.className = "cbx-option";
        if (it.value === state.value) li.classList.add("is-selected");
        if (idx === state.activeIndex) li.classList.add("is-active");
        li.setAttribute("role", "option");
        li.dataset.value = String(it.value);
        li.innerHTML = (formatItem || defaultFormatItem)(it);
        li.addEventListener("mousedown", (e) => {
          e.preventDefault();
          selectItem(it);
        });
        list.appendChild(li);
      });
      if (window.lucide) window.lucide.createIcons({ icons: window.lucide.icons });
    };

    const selectItem = (it) => {
      const changed = state.value !== it.value;
      state.value = it.value;
      state.filter = "";
      searchInput.value = "";
      renderField();
      close();
      if (changed && onChange) onChange(it);
    };

    const findParentField = () => wrap.closest(".hc-select.is-enhanced");

    const positionPanel = () => {
      if (!wrap.classList.contains("is-open")) return;

      const rect = field.getBoundingClientRect();
      const parentField = findParentField();
      const pad = 14;
      const gap = 6;
      const maxAllowedWidth = Math.max(180, window.innerWidth - pad * 2);

      const desiredWidth = parentField ? rect.width : Math.min(420, maxAllowedWidth);
      panel.style.minWidth = rect.width + "px";
      panel.style.width = parentField ? rect.width + "px" : "max-content";
      panel.style.maxWidth = Math.min(parentField ? rect.width : 420, maxAllowedWidth) + "px";

      const spaceBelow = window.innerHeight - rect.bottom - pad;
      const spaceAbove = rect.top - pad;
      const openUp = spaceBelow < 180 && spaceAbove > spaceBelow;

      let maxHeight;
      if (openUp) {
        maxHeight = Math.min(320, Math.max(160, spaceAbove - gap));
        panel.style.top = Math.max(pad, rect.top - gap - maxHeight) + "px";
      } else {
        maxHeight = Math.min(320, Math.max(160, spaceBelow - gap));
        panel.style.top = rect.bottom + gap + "px";
      }
      panel.style.bottom = "auto";

      let left = rect.left;
      if (left + desiredWidth > window.innerWidth - pad) {
        left = Math.max(pad, window.innerWidth - pad - desiredWidth);
      }
      panel.style.left = left + "px";
      panel.style.right = "auto";
      panel.style.maxHeight = maxHeight + "px";
    };

    const repositionPanel = () => positionPanel();

    const open = (seed = "") => {
      if (state.disabled) return;
      if (wrap.classList.contains("is-open")) return;
      wrap.classList.add("is-open");
      if (panel.parentElement !== document.body) document.body.appendChild(panel);
      panel.classList.add("is-open");
      field.setAttribute("aria-expanded", "true");
      searchInput.tabIndex = 0;
      const parentField = findParentField();
      if (parentField) parentField.classList.add("is-focused");
      searchInput.value = seed;
      state.filter = seed;
      state.activeIndex = seed ? 0 : -1;
      renderList();
      positionPanel();
      requestAnimationFrame(positionPanel);
      setTimeout(() => {
        searchInput.focus();
        searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
      }, 10);
    };

    const close = () => {
      wrap.classList.remove("is-open");
      panel.classList.remove("is-open");
      field.setAttribute("aria-expanded", "false");
      searchInput.tabIndex = wrap.classList.contains("is-open") && !state.disabled ? 0 : -1;
      state.filter = "";
      state.activeIndex = -1;
      searchInput.value = "";
      const parentField = findParentField();
      if (parentField) parentField.classList.remove("is-focused");
      renderField();
      if (panel.parentElement === document.body) wrap.appendChild(panel);
    };

    const syncDisabled = () => {
      if (state.disabled && wrap.classList.contains("is-open")) close();
      wrap.classList.toggle("is-disabled", state.disabled);
      field.setAttribute("aria-disabled", state.disabled ? "true" : "false");
      field.tabIndex = state.disabled ? -1 : 0;
      searchInput.tabIndex = -1;
    };

    field.addEventListener("click", () => {
      if (state.disabled) {
        if (onDisabledClick) onDisabledClick();
        return;
      }
      if (wrap.classList.contains("is-open")) {
        searchInput.focus();
      } else {
        open();
      }
    });

    field.addEventListener("keydown", (e) => {
      if (e.target === searchInput) return;
      if (state.disabled) {
        if ((e.key === "Enter" || e.key === " ") && onDisabledClick) onDisabledClick();
        return;
      }
      if (wrap.classList.contains("is-open")) return;
      if (e.key === "Enter" || e.key === " " || e.key === "ArrowDown") {
        e.preventDefault();
        open();
      } else if (e.key.length === 1 && !e.altKey && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        open(e.key);
      }
    });

    searchInput.addEventListener("input", () => {
      state.filter = searchInput.value;
      state.activeIndex = state.filter ? 0 : -1;
      renderList();
      positionPanel();
    });

    searchInput.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        e.preventDefault();
        close();
        field.focus();
      } else if (e.key === "ArrowDown") {
        e.preventDefault();
        state.activeIndex = Math.min(state.filtered.length - 1, state.activeIndex + 1);
        renderList();
        const active = list.querySelector(".cbx-option.is-active");
        if (active) active.scrollIntoView({ block: "nearest" });
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        state.activeIndex = Math.max(0, state.activeIndex - 1);
        renderList();
        const active = list.querySelector(".cbx-option.is-active");
        if (active) active.scrollIntoView({ block: "nearest" });
      } else if (e.key === "Enter") {
        e.preventDefault();
        const pick = state.activeIndex >= 0 ? state.filtered[state.activeIndex] : state.filtered[0];
        if (pick) selectItem(pick);
      } else if (e.key === "Tab") {
        close();
      }
    });

    document.addEventListener("mousedown", (e) => {
      if (!wrap.contains(e.target) && !panel.contains(e.target)) close();
    });
    window.addEventListener("resize", repositionPanel);
    window.addEventListener("scroll", repositionPanel, true);

    renderField();
    syncDisabled();

    return {
      element: wrap,
      setItems: (newItems) => {
        state.items = newItems || [];
        renderField();
        if (wrap.classList.contains("is-open")) {
          renderList();
          positionPanel();
        }
      },
      setValue: (v) => { state.value = v; renderField(); },
      setDisabled: (isDisabled) => {
        state.disabled = !!isDisabled;
        syncDisabled();
      },
      getValue: () => state.value,
      getItem: () => state.items.find((x) => x.value === state.value) || null,
      open, close
    };
  };

  // ---------- Phone country combobox ----------
  const countryHosts = Array.from(document.querySelectorAll("[data-country-select]"));

  if (countryHosts.length) {
    const fmtItem = (it) => `<img class="cbx-flag" src="${flagUrl(it.value)}" srcset="${flagUrl2x(it.value)} 2x" width="22" height="16" alt="" loading="lazy"><span class="cbx-name">${it.name}</span><span class="cbx-dial">${it.dial}</span>`;
    const fmtSelected = (it) => `<img class="cbx-flag" src="${flagUrl(it.value)}" srcset="${flagUrl2x(it.value)} 2x" width="22" height="16" alt=""><span class="cbx-dial">${it.dial}</span>`;

    const items = buildCountryItems(COUNTRIES_FALLBACK);
    const countryComboboxes = countryHosts.map((countryHost) => {
      const scope = countryHost.closest("[data-phone-group]") || countryHost.closest(".hc-phone") || countryHost.parentElement;
      const phoneInput = scope ? scope.querySelector("[data-phone-input]") : null;
      const phoneCountryInput = scope ? scope.querySelector("[data-phone-country-input]") : null;

      const applyCountry = (it) => {
        if (!it) return;
        if (phoneCountryInput) phoneCountryInput.value = it.dial;
        if (phoneInput) {
          phoneInput.maxLength = it.length;
          phoneInput.pattern = `\\d{${it.length}}`;
          phoneInput.setAttribute("aria-label", `Phone number (${it.length} digits)`);
          if (phoneInput.value.length > it.length) phoneInput.value = phoneInput.value.slice(0, it.length);
        }
      };

      const countryCbx = createCombobox({
        host: countryHost,
        items,
        value: "IN",
        placeholder: "+code",
        searchPlaceholder: "Search country or code",
        onChange: applyCountry,
        formatItem: fmtItem,
        formatSelected: fmtSelected,
        emptyText: "No country found"
      });
      applyCountry(items.find((c) => c.value === "IN"));

      if (phoneInput) {
        phoneInput.addEventListener("input", () => {
          const digitsOnly = phoneInput.value.replace(/\D+/g, "");
          const len = countryCbx.getItem() ? countryCbx.getItem().length : 10;
          phoneInput.value = digitsOnly.slice(0, len);
        });
      }

      return countryCbx;
    });

    fetchCountriesFromApi().then((rows) => {
      if (!rows || rows.length < 50) return;
      const merged = buildCountryItems(rows);
      countryComboboxes.forEach((countryCbx) => countryCbx.setItems(merged));
    });
  }

  // ---------- State + city comboboxes ----------
  const stateSelect = document.querySelector("[data-state-select]");
  const citySelect = document.querySelector("[data-city-select]");

  if (stateSelect && citySelect) {
    const sortAlpha = (arr) => arr.slice().sort((a, b) => a.localeCompare(b));

    const syncSelect = (selectEl, value, options) => {
      selectEl.innerHTML = "";
      const ph = document.createElement("option");
      ph.value = "";
      ph.disabled = true;
      ph.hidden = true;
      selectEl.appendChild(ph);
      options.forEach((v) => {
        const opt = document.createElement("option");
        opt.value = v;
        opt.textContent = v;
        selectEl.appendChild(opt);
      });
      if (value && options.includes(value)) {
        selectEl.value = value;
      } else {
        ph.selected = true;
      }
    };

    const valueItems = (arr) => arr.map((v) => ({ value: v, name: v, search: v.toLowerCase() }));

    const fmtSimple = (it) => `<span class="cbx-name">${it.name}</span>`;

    const stateWrap = stateSelect.parentElement;
    const cityWrap = citySelect.parentElement;
    stateWrap.classList.add("is-enhanced");
    cityWrap.classList.add("is-enhanced");

    const setFilled = (wrap, filled) => wrap.classList.toggle("is-filled", !!filled);

    let activeRequestToken = 0;
    let stateAlertTimer = null;

    const clearStateAlert = () => {
      if (stateAlertTimer) clearTimeout(stateAlertTimer);
      stateAlertTimer = null;
      stateWrap.classList.remove("is-required-alert");
    };

    const highlightStateRequired = () => {
      if (stateSelect.value) return;
      stateWrap.classList.add("is-required-alert");
      if (stateAlertTimer) clearTimeout(stateAlertTimer);
      stateAlertTimer = setTimeout(clearStateAlert, 1800);
    };

    const cityCbx = createCombobox({
      host: cityWrap,
      items: [],
      value: null,
      placeholder: "",
      searchPlaceholder: "Search city",
      formatItem: fmtSimple,
      formatSelected: fmtSimple,
      emptyText: "Select a state first",
      disabled: true,
      onDisabledClick: highlightStateRequired,
      onChange: (it) => {
        syncSelect(citySelect, it.value, cityCbx._currentList || []);
        setFilled(cityWrap, true);
      }
    });

    const setCityLocked = (locked) => {
      cityCbx.setDisabled(locked);
      citySelect.disabled = locked;
      cityWrap.classList.toggle("is-locked", locked);
    };

    const setCities = (cities) => {
      const sorted = sortAlpha(cities);
      cityCbx._currentList = sorted;
      cityCbx.setItems(valueItems(sorted));
      setCityLocked(!stateSelect.value);
      const currentVal = citySelect.value;
      if (currentVal && sorted.includes(currentVal)) {
        syncSelect(citySelect, currentVal, sorted);
      } else {
        syncSelect(citySelect, "", sorted);
        cityCbx.setValue(null);
        setFilled(cityWrap, false);
      }
    };

    const stateNames = Object.keys(FALLBACK_CITIES_BY_STATE);
    const stateCbx = createCombobox({
      host: stateWrap,
      items: valueItems(sortAlpha(stateNames)),
      value: null,
      placeholder: "",
      searchPlaceholder: "Search state",
      formatItem: fmtSimple,
      formatSelected: fmtSimple,
      emptyText: "No state found",
      onChange: async (it) => {
        clearStateAlert();
        const state = it.value;
        syncSelect(stateSelect, state, stateCbx._currentList || sortAlpha(stateNames));
        setFilled(stateWrap, true);
        cityCbx.setValue(null);
        setFilled(cityWrap, false);

        const fallbackCities = FALLBACK_CITIES_BY_STATE[state] || [];
        const cached = readCache("cities_" + state);
        const initial = cached && cached.length ? cached : fallbackCities;
        const reqToken = ++activeRequestToken;
        setCities(initial);

        if (cached) return;

        try {
          const apiCities = await fetchCitiesFromApi(state);
          if (reqToken !== activeRequestToken) return;
          const merged = Array.from(new Set([...(apiCities || []), ...fallbackCities]));
          setCities(merged);
        } catch (_) {
          if (reqToken !== activeRequestToken) return;
          setCities(fallbackCities);
        }
      }
    });
    stateCbx._currentList = sortAlpha(stateNames);
    syncSelect(stateSelect, "", stateCbx._currentList);
    syncSelect(citySelect, "", []);
    setCityLocked(true);

    fetchStatesFromApi()
      .then((apiStates) => {
        if (!Array.isArray(apiStates) || apiStates.length < 20) return;
        const merged = sortAlpha(Array.from(new Set([...apiStates, ...stateNames])));
        stateCbx._currentList = merged;
        stateCbx.setItems(valueItems(merged));
      })
      .catch(() => {});
  }
});

/* ============================================================
   Color theme switcher
   ============================================================ */
(function () {
  const STORAGE_KEY = "oda:color-theme";
  const THEMES = new Set([
    "current",
    "sapphire",
  ]);
  const THEME_COLORS = {
    current: "#0f3b45",
    sapphire: "#1e3a8a",
  };

  function getStoredTheme() {
    try {
      const value = sessionStorage.getItem(STORAGE_KEY);
      if (THEMES.has(value)) return value;
      sessionStorage.removeItem(STORAGE_KEY);
      return "current";
    } catch (error) {
      return "current";
    }
  }

  function setStoredTheme(theme) {
    try {
      if (theme === "current") {
        sessionStorage.removeItem(STORAGE_KEY);
      } else {
        sessionStorage.setItem(STORAGE_KEY, theme);
      }
    } catch (error) {}
  }

  function applyTheme(theme) {
    const activeTheme = THEMES.has(theme) ? theme : "current";

    if (activeTheme === "current") {
      delete document.documentElement.dataset.colorTheme;
    } else {
      document.documentElement.dataset.colorTheme = activeTheme;
    }

    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
    if (metaThemeColor) {
      metaThemeColor.setAttribute("content", THEME_COLORS[activeTheme]);
    }

    document.querySelectorAll("[data-theme-option]").forEach((button) => {
      const isActive = button.dataset.themeOption === activeTheme;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });
  }

  function initThemeSwitcher() {
    const switchers = document.querySelectorAll("[data-theme-switcher]");
    const storedTheme = getStoredTheme();
    applyTheme(storedTheme);

    switchers.forEach((switcher) => {
      switcher.querySelectorAll("[data-theme-option]").forEach((button) => {
        button.addEventListener("click", () => {
          const nextTheme = THEMES.has(button.dataset.themeOption) ? button.dataset.themeOption : "current";
          applyTheme(nextTheme);
          setStoredTheme(nextTheme);
        });
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initThemeSwitcher, { once: true });
  } else {
    initThemeSwitcher();
  }
})();

/* ============================================================
   Currency switcher + live conversion
   ============================================================ */
(function () {
  const FX = {
    USD: { rate: 1.0, symbol: "$", name: "US Dollar" },
    INR: { rate: 83.5, symbol: "₹", name: "Indian Rupee" },
    GBP: { rate: 0.79, symbol: "£", name: "British Pound" },
    EUR: { rate: 0.92, symbol: "€", name: "Euro" },
    CAD: { rate: 1.35, symbol: "CA$", name: "Canadian Dollar" },
    AUD: { rate: 1.52, symbol: "A$", name: "Australian Dollar" },
    AED: { rate: 3.67, symbol: "AED ", name: "UAE Dirham" },
    NZD: { rate: 1.65, symbol: "NZ$", name: "New Zealand Dollar" },
  };

  const STORAGE_KEY = "oda:currency";

  function getStored() {
    try {
      const v = localStorage.getItem(STORAGE_KEY);
      return v && FX[v] ? v : null;
    } catch (e) {
      return null;
    }
  }

  function setStored(code) {
    try {
      localStorage.setItem(STORAGE_KEY, code);
    } catch (e) {}
  }

  function convert(value, from, to) {
    if (from === to) return value;
    const usd = value / FX[from].rate;
    return usd * FX[to].rate;
  }

  function niceRound(value) {
    const abs = Math.abs(value);
    if (abs >= 1_000_000) return Math.round(value / 10000) * 10000;
    if (abs >= 100_000) return Math.round(value / 1000) * 1000;
    if (abs >= 10_000) return Math.round(value / 100) * 100;
    if (abs >= 1_000) return Math.round(value / 10) * 10;
    if (abs >= 100) return Math.round(value / 5) * 5;
    if (abs >= 10) return Math.round(value);
    return Math.round(value * 10) / 10;
  }

  function abbreviate(value) {
    const abs = Math.abs(value);
    if (abs >= 10_000_000) return (value / 10_000_000).toFixed(1).replace(/\.0$/, "") + "Cr";
    if (abs >= 100_000) return (value / 100_000).toFixed(1).replace(/\.0$/, "") + "L";
    if (abs >= 1000) return Math.round(value / 1000).toLocaleString("en-US") + "k";
    return Math.round(value).toLocaleString("en-US");
  }

  function format(value, code, hint) {
    const fx = FX[code] || FX.USD;
    const rounded = niceRound(value);
    // INR converts to very large numbers — abbreviate when over ~1L
    if (code === "INR" && Math.abs(rounded) >= 100_000) {
      return fx.symbol + abbreviate(rounded);
    }
    // For other currencies keep abbreviated form if the source used "k"
    if (hint === "k" && Math.abs(rounded) >= 1000) {
      return fx.symbol + abbreviate(rounded);
    }
    return fx.symbol + Math.round(rounded).toLocaleString("en-US");
  }

  function refreshMoney(target) {
    document.querySelectorAll("[data-money]").forEach((el) => {
      const v = parseFloat(el.dataset.money);
      const from = el.dataset.currency || "USD";
      const hint = el.dataset.moneyHint || "";
      if (!isFinite(v)) return;
      // Cache the original markup once so "Local" can restore it
      if (el.dataset.moneyOriginal === undefined) {
        el.dataset.moneyOriginal = el.innerHTML;
      }
      if (!target || target === "local" || !FX[target]) {
        el.innerHTML = el.dataset.moneyOriginal;
      } else {
        el.textContent = format(convert(v, from, target), target, hint);
      }
    });
    document.documentElement.dataset.currency = target || "local";
  }

  function initSwitcher() {
    const switches = document.querySelectorAll("[data-currency-switch]");
    if (!switches.length) return;
    const current = getStored();

    function setCurrent(code) {
      const label = code && FX[code] ? code : "Local";
      switches.forEach((sw) => {
        const lbl = sw.querySelector("[data-currency-label]");
        if (lbl) lbl.textContent = label;
        sw.querySelectorAll("[data-currency-option]").forEach((btn) => {
          btn.setAttribute(
            "aria-current",
            btn.dataset.currencyOption === code ? "true" : "false"
          );
        });
      });
      if (code && FX[code]) setStored(code);
      refreshMoney(code);
    }

    function closeAll() {
      switches.forEach((sw) => {
        sw.classList.remove("is-open");
        const trigger = sw.querySelector("[data-currency-trigger]");
        if (trigger) trigger.setAttribute("aria-expanded", "false");
      });
    }

    switches.forEach((sw) => {
      const trigger = sw.querySelector("[data-currency-trigger]");
      if (trigger) {
        trigger.addEventListener("click", (e) => {
          e.stopPropagation();
          const open = sw.classList.contains("is-open");
          closeAll();
          if (!open) {
            sw.classList.add("is-open");
            trigger.setAttribute("aria-expanded", "true");
          }
        });
      }
      sw.querySelectorAll("[data-currency-option]").forEach((btn) => {
        btn.addEventListener("click", () => {
          setCurrent(btn.dataset.currencyOption);
          closeAll();
        });
      });
    });

    document.addEventListener("click", (e) => {
      if (!e.target.closest("[data-currency-switch]")) closeAll();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeAll();
    });

    setCurrent(current);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSwitcher, { once: true });
  } else {
    initSwitcher();
  }
})();

(function () {
  function initCourseCarousels() {
    const carousels = Array.from(document.querySelectorAll("[data-course-carousel]"));
    if (!carousels.length) return;

    const reducedMotion = window.matchMedia
      ? window.matchMedia("(prefers-reduced-motion: reduce)")
      : { matches: false };

    carousels.forEach((carousel) => {
      const track = carousel.querySelector("[data-course-track]");
      const firstSet = track ? track.querySelector(".dynamic-course-set") : null;
      if (!track || !firstSet) return;

      let setWidth = 0;
      let offset = 0;
      let targetOffset = 0;
      let pointerStartX = 0;
      let pointerStartOffset = 0;
      let isDragging = false;
      let isHovering = false;
      let suppressClick = false;
      let suppressClickTimer = null;
      let resumeAt = 0;

      const normalize = (value) => {
        if (!setWidth) return value;
        return ((value % setWidth) + setWidth) % setWidth;
      };

      const render = () => {
        track.style.transform = "translate3d(" + (-offset).toFixed(2) + "px, 0, 0)";
      };

      const moveTo = (value) => {
        targetOffset = normalize(value);
        offset = targetOffset;
        render();
      };

      const pauseAuto = (duration) => {
        resumeAt = performance.now() + duration;
      };

      const measure = () => {
        setWidth = firstSet.getBoundingClientRect().width;
        moveTo(targetOffset);
      };

      const finishDrag = (event) => {
        if (!isDragging) return;
        isDragging = false;
        carousel.classList.remove("is-dragging");
        pauseAuto(1800);

        if (event && carousel.releasePointerCapture) {
          try {
            carousel.releasePointerCapture(event.pointerId);
          } catch (error) {}
        }

        if (suppressClick) {
          window.clearTimeout(suppressClickTimer);
          suppressClickTimer = window.setTimeout(() => {
            suppressClick = false;
          }, 220);
        }
      };

      carousel.classList.add("is-scroll-ready");
      measure();

      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(measure);
      }

      window.addEventListener("resize", measure, { passive: true });

      carousel.addEventListener("mouseenter", () => {
        isHovering = true;
      });

      carousel.addEventListener("mouseleave", () => {
        isHovering = false;
      });

      carousel.addEventListener("focusin", () => {
        isHovering = true;
      });

      carousel.addEventListener("focusout", () => {
        isHovering = false;
      });

      carousel.addEventListener("wheel", (event) => {
        if (!setWidth) return;
        const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
          ? event.deltaX
          : event.deltaY;
        if (!delta) return;

        event.preventDefault();
        moveTo(targetOffset + delta * 0.85);
        pauseAuto(1300);
      }, { passive: false });

      carousel.addEventListener("pointerdown", (event) => {
        if (event.pointerType === "mouse" && event.button !== 0) return;

        isDragging = true;
        suppressClick = false;
        pointerStartX = event.clientX;
        pointerStartOffset = targetOffset;
        carousel.classList.add("is-dragging");
        pauseAuto(2200);

        if (carousel.setPointerCapture) {
          carousel.setPointerCapture(event.pointerId);
        }
      });

      carousel.addEventListener("pointermove", (event) => {
        if (!isDragging) return;

        const delta = pointerStartX - event.clientX;
        if (Math.abs(delta) > 4) suppressClick = true;
        moveTo(pointerStartOffset + delta);

        if (event.cancelable) {
          event.preventDefault();
        }
      });

      carousel.addEventListener("pointerup", finishDrag);
      carousel.addEventListener("pointercancel", finishDrag);
      carousel.addEventListener("lostpointercapture", finishDrag);

      carousel.addEventListener("click", (event) => {
        if (!suppressClick) return;
        event.preventDefault();
        event.stopPropagation();
        suppressClick = false;
      }, true);

      const tick = (now) => {
        if (!reducedMotion.matches && !isDragging && !isHovering && now > resumeAt) {
          moveTo(targetOffset + 0.42);
        }

        window.requestAnimationFrame(tick);
      };

      window.requestAnimationFrame(tick);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCourseCarousels, { once: true });
  } else {
    initCourseCarousels();
  }
})();

(function () {
  function initContactFab() {
    const fab = document.querySelector("[data-contact-fab]");
    if (!fab) return;

    const reduced = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    function measure() {
      // Temporarily disable transitions and force the expanded layout so we can
      // read the natural full-content width, then restore.
      const prevTransition = fab.style.transition;
      fab.style.transition = "none";
      fab.style.width = "auto";
      const w = Math.ceil(fab.getBoundingClientRect().width);
      fab.style.width = "";
      // Force a reflow before re-enabling transitions so the next change animates.
      void fab.offsetWidth;
      fab.style.transition = prevTransition;
      fab.style.setProperty("--contact-fab-expanded", w + "px");
    }

    // Measure once fonts are ready so the width matches the actual rendered label.
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(measure);
    } else {
      measure();
    }
    window.addEventListener("resize", measure);

    if (reduced) return;

    const rand = (min, max) => Math.floor(min + Math.random() * (max - min));
    const EXPAND_MS = 2800;
    let timer = null;
    let userHovered = false;

    fab.addEventListener("mouseenter", () => { userHovered = true; });
    fab.addEventListener("mouseleave", () => { userHovered = false; });
    fab.addEventListener("focus", () => { userHovered = true; });
    fab.addEventListener("blur", () => { userHovered = false; });

    function schedule() {
      const delay = rand(7000, 16000);
      timer = window.setTimeout(expand, delay);
    }

    function expand() {
      if (document.hidden || userHovered) {
        schedule();
        return;
      }
      fab.classList.add("is-expanded");
      timer = window.setTimeout(collapse, EXPAND_MS);
    }

    function collapse() {
      fab.classList.remove("is-expanded");
      schedule();
    }

    document.addEventListener("visibilitychange", () => {
      if (document.hidden && timer) {
        window.clearTimeout(timer);
        timer = null;
        fab.classList.remove("is-expanded");
      } else if (!document.hidden && !timer) {
        schedule();
      }
    });

    schedule();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initContactFab, { once: true });
  } else {
    initContactFab();
  }

  function initHiringProcess() {
    const root = document.querySelector("[data-hiring-process]");
    if (!root) return;

    const steps = Array.from(root.querySelectorAll(".cr-rail-step"));
    const titleEl = root.querySelector("[data-step-title]");
    const bodyEl = root.querySelector("[data-step-body]");
    const metaEl = root.querySelector("[data-rail-meta]");
    const stageEl = root.querySelector("[data-rail-stage]");
    const fillEl = root.querySelector("[data-rail-fill]");
    const prevBtn = root.querySelector("[data-rail-prev]");
    const nextBtn = root.querySelector("[data-rail-next]");
    if (!steps.length || !titleEl || !bodyEl) return;

    const total = steps.length;
    let currentIdx = 0;

    const pad = (n) => String(n).padStart(2, "0");

    const activate = (idx) => {
      idx = Math.max(0, Math.min(total - 1, idx));
      currentIdx = idx;
      steps.forEach((s, i) => {
        const on = i === idx;
        const passed = i < idx;
        s.classList.toggle("is-active", on);
        s.classList.toggle("is-passed", passed);
        s.setAttribute("aria-selected", on ? "true" : "false");
      });
      const step = steps[idx];
      titleEl.textContent = step.dataset.title || "";
      bodyEl.innerHTML = step.dataset.body || "";
      if (metaEl) metaEl.innerHTML = step.dataset.meta || "";
      if (stageEl) stageEl.textContent = "Step " + pad(idx + 1) + " of " + pad(total);
      if (fillEl) {
        const pct = total > 1 ? (idx / (total - 1)) * 100 : 0;
        fillEl.style.width = pct + "%";
      }
      if (prevBtn) prevBtn.disabled = idx === 0;
      if (nextBtn) nextBtn.disabled = idx === total - 1;
    };

    steps.forEach((step, i) => {
      step.addEventListener("click", () => activate(i));
      step.addEventListener("focus", () => activate(i));
    });

    if (prevBtn) prevBtn.addEventListener("click", () => activate(currentIdx - 1));
    if (nextBtn) nextBtn.addEventListener("click", () => activate(currentIdx + 1));

    const initial = steps.findIndex((s) => s.classList.contains("is-active"));
    activate(initial >= 0 ? initial : 0);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHiringProcess, { once: true });
  } else {
    initHiringProcess();
  }

  function initCountrySnapshotRail() {
    const rails = Array.from(document.querySelectorAll(".country-snapshot"));
    if (!rails.length) return;

    rails.forEach((rail) => {
      if (rail.parentElement !== document.body) {
        document.body.appendChild(rail);
      }

      if (!rail.hasAttribute("tabindex")) rail.setAttribute("tabindex", "0");
      rail.setAttribute("role", "button");
      rail.setAttribute("aria-expanded", "false");

      rail.addEventListener("click", (e) => {
        const isOpen = rail.classList.toggle("is-open");
        rail.setAttribute("aria-expanded", isOpen ? "true" : "false");
        e.stopPropagation();
      });

      rail.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          rail.click();
        } else if (e.key === "Escape" && rail.classList.contains("is-open")) {
          rail.classList.remove("is-open");
          rail.setAttribute("aria-expanded", "false");
        }
      });
    });

    document.addEventListener("click", (e) => {
      document.querySelectorAll(".country-snapshot.is-open").forEach((rail) => {
        if (!rail.contains(e.target)) {
          rail.classList.remove("is-open");
          rail.setAttribute("aria-expanded", "false");
        }
      });
    });

    const parseRgb = (str) => {
      if (!str) return null;
      const m = str.match(/[\d.]+/g);
      if (!m || m.length < 3) return null;
      const [r, g, b, a] = m.map(Number);
      return { r, g, b, a: a == null ? 1 : a };
    };

    const getOpaqueBg = (el) => {
      let node = el;
      while (node && node !== document.documentElement) {
        const c = parseRgb(getComputedStyle(node).backgroundColor);
        if (c && c.a > 0.5) return c;
        node = node.parentElement;
      }
      return { r: 255, g: 255, b: 255, a: 1 };
    };

    const isLight = (c) => (0.299 * c.r + 0.587 * c.g + 0.114 * c.b) > 165;

    const updateRailContrast = () => {
      rails.forEach((rail) => {
        const rect = rail.getBoundingClientRect();
        const probeX = Math.max(2, rect.left - 12);
        const probeY = rect.top + rect.height / 2;
        const prevPe = rail.style.pointerEvents;
        rail.style.pointerEvents = "none";
        const behind = document.elementFromPoint(probeX, probeY);
        rail.style.pointerEvents = prevPe;
        if (!behind) return;
        const bg = getOpaqueBg(behind);
        rail.classList.toggle("on-light", isLight(bg));
      });
    };

    let ticking = false;
    const schedule = () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        updateRailContrast();
        ticking = false;
      });
    };

    window.addEventListener("scroll", schedule, { passive: true });
    window.addEventListener("resize", schedule);
    schedule();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCountrySnapshotRail, { once: true });
  } else {
    initCountrySnapshotRail();
  }
})();
