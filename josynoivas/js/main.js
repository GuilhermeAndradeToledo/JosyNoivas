document.addEventListener("DOMContentLoaded", () => {
  const swipe = (el, esq, dir) => {
    let x0 = null,
      y0 = 0;
    el.addEventListener(
      "touchstart",
      (e) => {
        x0 = e.touches[0].clientX;
        y0 = e.touches[0].clientY;
      },
      { passive: true },
    );
    el.addEventListener(
      "touchend",
      (e) => {
        if (x0 === null) return;
        const dx = e.changedTouches[0].clientX - x0;
        const dy = e.changedTouches[0].clientY - y0;
        x0 = null;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5)
          (dx < 0 ? esq : dir)();
      },
      { passive: true },
    );
  };

  const toggle = document.querySelector(".menu-toggle");
  const menu = document.querySelector(".menu-principal");
  if (toggle && menu) {
    toggle.addEventListener("click", () => {
      const aberto = menu.classList.toggle("aberto");
      toggle.setAttribute("aria-expanded", aberto);
    });
  }

  const buscaBtn = document.querySelector(".busca-abrir");
  const buscaPainel = document.querySelector(".busca-painel");
  if (buscaBtn && buscaPainel) {
    buscaBtn.addEventListener("click", () => {
      buscaPainel.hidden = !buscaPainel.hidden;
      buscaBtn.setAttribute("aria-expanded", !buscaPainel.hidden);
      if (!buscaPainel.hidden) buscaPainel.querySelector("input").focus();
    });
  }

  const banner = document.querySelector(".banner");
  if (banner) {
    const slides = banner.querySelectorAll(".banner-slide");
    const pontos = banner.querySelector(".pontos");
    if (slides.length > 1 && pontos) {
      let atual = 0;
      let timer;

      slides.forEach((_, i) => {
        const b = document.createElement("button");
        b.type = "button";
        b.setAttribute("aria-label", "Banner " + (i + 1));
        b.addEventListener("click", () => {
          ir(i);
          reiniciar();
        });
        pontos.appendChild(b);
      });

      const ir = (n) => {
        atual = (n + slides.length) % slides.length;
        slides.forEach((s, i) => s.classList.toggle("ativo", i === atual));
        pontos
          .querySelectorAll("button")
          .forEach((b, i) => b.classList.toggle("ativo", i === atual));
      };

      const reiniciar = () => {
        clearInterval(timer);
        timer = setInterval(() => ir(atual + 1), 6000);
      };

      banner.querySelector(".seta-ant").addEventListener("click", () => {
        ir(atual - 1);
        reiniciar();
      });
      banner.querySelector(".seta-prox").addEventListener("click", () => {
        ir(atual + 1);
        reiniciar();
      });
      ir(0);
      reiniciar();
      swipe(
        banner,
        () => {
          ir(atual + 1);
          reiniciar();
        },
        () => {
          ir(atual - 1);
          reiniciar();
        },
      );
    }
  }

  const abas = document.querySelectorAll(".aba");
  if (abas.length) {
    const cards = document.querySelectorAll("#grade-acessorios .card-produto");
    const barra = abas[0].parentElement;
    const linha = document.createElement("span");
    linha.className = "aba-linha";
    barra.appendChild(linha);

    const mover = () => {
      const a = barra.querySelector(".aba.ativo");
      if (!a) return;
      linha.style.width = a.offsetWidth + "px";
      linha.style.transform = `translateX(${a.offsetLeft}px)`;
    };
    const filtrar = (tipo) => {
      let n = 0;
      cards.forEach((c) => {
        c.hidden = !(c.dataset.tipo === tipo && n++ < 4);
      });
      abas.forEach((a) => a.classList.toggle("ativo", a.dataset.tipo === tipo));
      mover();
    };
    abas.forEach((a) =>
      a.addEventListener("click", () => filtrar(a.dataset.tipo)),
    );
    mover();
    addEventListener("load", mover);
    addEventListener("resize", mover);
  }

  const toast = (msg) => {
    let t = document.querySelector(".toast");
    if (!t) {
      t = document.createElement("div");
      t.className = "toast";
      t.setAttribute("role", "status");
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add("on");
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove("on"), 2200);
  };

  document.querySelectorAll(".fav-btn").forEach((b) =>
    b.addEventListener("click", async () => {
      const d = document.body.dataset;
      if (d.logado !== "1") {
        window.location.href =
          d.login +
          "?voltar=" +
          encodeURIComponent(location.pathname + location.search);
        return;
      }
      try {
        const r = await fetch(d.favUrl, {
          method: "POST",
          body: new URLSearchParams({ produto: b.dataset.fav, csrf: d.csrf }),
        });
        const j = await r.json();
        if (j.success) {
          b.classList.toggle("ativo", j.favorito);
          b.setAttribute("aria-pressed", j.favorito);
          toast(j.favorito ? "Salvo nos favoritos" : "Removido dos favoritos");
        }
      } catch (e) {}
    }),
  );

  document
    .querySelectorAll("[data-auto-submit]")
    .forEach((el) => el.addEventListener("change", () => el.form.submit()));

  const principal = document.getElementById("foto-principal");
  if (principal) {
    const caixa = principal.parentElement;
    caixa.addEventListener("mousemove", (e) => {
      const r = caixa.getBoundingClientRect();
      principal.style.transformOrigin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
    });
  }
  document.querySelectorAll(".miniatura").forEach((m) =>
    m.addEventListener("click", () => {
      if (principal) {
        principal.classList.add("trocando");
        setTimeout(() => {
          principal.onload = () => principal.classList.remove("trocando");
          principal.src = m.dataset.src;
        }, 250);
      }
      document
        .querySelectorAll(".miniatura")
        .forEach((x) => x.classList.toggle("ativo", x === m));
    }),
  );

  const wa = document.querySelector("[data-wa]");
  const was = document.querySelectorAll("[data-wa]");
  document.querySelectorAll(".tam").forEach((t) =>
    t.addEventListener("click", () => {
      const jaAtivo = t.classList.contains("ativo");
      document.querySelectorAll(".tam").forEach((x) => {
        x.classList.remove("ativo");
        x.setAttribute("aria-pressed", "false");
      });
      if (!jaAtivo) {
        t.classList.add("ativo");
        t.setAttribute("aria-pressed", "true");
      }
      was.forEach((w) => {
        w.href = w.dataset.base + (jaAtivo ? "" : "&tamanho=" + t.dataset.tam);
      });
    }),
  );

  const abrirPergunta = document.querySelector("[data-abrir-pergunta]");
  const formPergunta = document.getElementById("form-pergunta");
  if (abrirPergunta && formPergunta) {
    abrirPergunta.addEventListener("click", () => {
      formPergunta.hidden = !formPergunta.hidden;
      if (!formPergunta.hidden)
        formPergunta.querySelector("input[name=nome]").focus();
    });
  }

  const mascara = (seletor, formatar) =>
    document.querySelectorAll(seletor).forEach((campo) =>
      campo.addEventListener("input", () => {
        campo.value = formatar(campo.value.replace(/\D/g, ""));
      }),
    );
  mascara("[data-mascara=cpf]", (d) =>
    d
      .slice(0, 11)
      .replace(/(\d{3})(\d)/, "$1.$2")
      .replace(/(\d{3})(\d)/, "$1.$2")
      .replace(/(\d{3})(\d{1,2})$/, "$1-$2"),
  );
  mascara("[data-mascara=telefone]", (d) => {
    d = d.slice(0, 11);
    if (d.length > 10) return d.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
    if (d.length > 6) return d.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
    if (d.length > 2) return d.replace(/(\d{2})(\d+)/, "($1) $2");
    return d;
  });

  document.querySelectorAll("form[data-confirm]").forEach((f) =>
    f.addEventListener("submit", (e) => {
      if (!confirm(f.dataset.confirm)) e.preventDefault();
    }),
  );

  const io = new IntersectionObserver(
    (entradas) =>
      entradas.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add("visivel");
          io.unobserve(e.target);
        }
      }),
    { threshold: 0.15 },
  );
  document
    .querySelectorAll(".secao, .card-produto, .ocasiao, .post")
    .forEach((el) => {
      el.classList.add("reveal");
      io.observe(el);
    });

  const topo = document.querySelector(".topo");
  if (topo)
    addEventListener(
      "scroll",
      () => topo.classList.toggle("rolou", scrollY > 40),
      { passive: true },
    );

  const arco = document.querySelector(".banner-slide.ativo .arco");
  if (arco && !matchMedia("(prefers-reduced-motion: reduce)").matches) {
    addEventListener(
      "scroll",
      () => {
        document.querySelectorAll(".banner .arco").forEach((a) => {
          a.style.transform = `translateY(${Math.min(scrollY, 600) * 0.08}px)`;
        });
      },
      { passive: true },
    );
  }

  document.querySelectorAll(".faq details").forEach((d) => {
    const s = d.querySelector("summary");
    s.addEventListener("click", (e) => {
      e.preventDefault();
      if (d._anim) d._anim.cancel();
      const abrindo = !d.open;
      if (abrindo) d.open = true;
      const ini = abrindo ? s.offsetHeight : d.offsetHeight;
      const fim = abrindo ? d.scrollHeight : s.offsetHeight;
      d.style.overflow = "hidden";
      d._anim = d.animate(
        { height: [ini + "px", fim + "px"] },
        { duration: 300, easing: "ease" },
      );
      d._anim.onfinish = () => {
        d.open = abrindo;
        d.style.overflow = "";
        d._anim = null;
      };
    });
  });

  const texto = document.querySelector(".artigo-texto");
  if (texto) {
    const barraP = document.createElement("div");
    barraP.className = "progresso";
    document.body.appendChild(barraP);
    const atualizar = () => {
      const r = texto.getBoundingClientRect();
      const total = r.height - innerHeight * 0.5;
      barraP.style.transform = `scaleX(${Math.min(1, Math.max(0, -r.top / total))})`;
    };
    addEventListener("scroll", atualizar, { passive: true });
    atualizar();
  }

  was.forEach((w) =>
    w.addEventListener("click", () => {
      const original = w.textContent;
      w.textContent = "Abrindo...";
      w.style.pointerEvents = "none";
      setTimeout(() => {
        w.textContent = original;
        w.style.pointerEvents = "";
      }, 2500);
    }),
  );

  const waFixo = document.querySelector(".wa-fixo");
  if (waFixo && wa) {
    new IntersectionObserver(([en]) =>
      waFixo.classList.toggle("mostrar", !en.isIntersecting),
    ).observe(wa);
  }

  const tema = document.querySelector("[data-tema]");
  if (tema) {
    const rot = () => {
      tema.textContent = document.body.classList.contains("escuro")
        ? "Modo claro"
        : "Modo escuro";
    };
    rot();
    tema.addEventListener("click", () => {
      const on = document.body.classList.toggle("escuro");
      try {
        localStorage.setItem("adminEscuro", on ? "1" : "0");
      } catch (e) {}
      rot();
    });
  }

  document
    .querySelectorAll("img[loading=lazy], #foto-principal")
    .forEach((img) => {
      if (img.complete) return;
      const caixa = img.closest(
        ".card-foto, .foto-principal, .faixa-foto, .post-img, .miniatura",
      );
      if (!caixa) return;
      caixa.classList.add("carregando");
      img.classList.add("some");
      const fim = () => {
        caixa.classList.remove("carregando");
        img.classList.remove("some");
      };
      img.addEventListener("load", fim, { once: true });
      img.addEventListener("error", fim, { once: true });
    });

  const comp = document.querySelector("[data-compartilhar]");
  if (comp) {
    comp.addEventListener("click", async () => {
      const dados = { title: comp.dataset.titulo, url: location.href };
      try {
        if (navigator.share) await navigator.share(dados);
        else {
          await navigator.clipboard.writeText(location.href);
          comp.textContent = "Link copiado!";
          setTimeout(() => (comp.textContent = "Compartilhar"), 2000);
        }
      } catch (e) {}
    });
  }

  if (document.querySelector("[data-filtros]")) {
    const fundo = document.createElement("div");
    fundo.className = "filtros-fundo";
    document.body.appendChild(fundo);
    const alternar = () => document.body.classList.toggle("filtros-aberto");
    document
      .querySelectorAll("[data-filtros]")
      .forEach((b) => b.addEventListener("click", alternar));
    fundo.addEventListener("click", alternar);
  }

  const caixaFoto = document.querySelector(".foto-principal");
  const minis = [...document.querySelectorAll(".miniatura")];
  if (caixaFoto && minis.length > 1) {
    const passo = (d) => {
      const a = minis.findIndex((m) => m.classList.contains("ativo"));
      minis[(a + d + minis.length) % minis.length].click();
    };
    swipe(
      caixaFoto,
      () => passo(1),
      () => passo(-1),
    );
  }

  if (caixaFoto && principal) {
    const lb = document.createElement("div");
    lb.className = "lightbox";
    lb.setAttribute("role", "dialog");
    lb.setAttribute("aria-modal", "true");
    lb.innerHTML =
      '<button type="button" class="lb-fechar" aria-label="Fechar">✕</button>' +
      '<button type="button" class="lb-seta lb-ant" aria-label="Anterior">‹</button>' +
      '<img alt="">' +
      '<button type="button" class="lb-seta lb-prox" aria-label="Próxima">›</button>' +
      '<span class="lb-cont"></span>';
    document.body.appendChild(lb);
    const img = lb.querySelector("img");
    const cont = lb.querySelector(".lb-cont");
    let fotos = [],
      i = 0;
    const mostrar = (n) => {
      i = (n + fotos.length) % fotos.length;
      img.src = fotos[i];
      cont.textContent = i + 1 + " / " + fotos.length;
    };
    const fechar = () => {
      lb.classList.remove("aberto");
      document.body.classList.remove("lb-aberto");
    };
    caixaFoto.addEventListener("click", () => {
      fotos = minis.map((m) => m.dataset.src);
      let ini = minis.findIndex((m) => m.classList.contains("ativo"));
      if (!fotos.length) {
        fotos = [principal.src];
        ini = 0;
      }
      lb.classList.toggle("unica", fotos.length < 2);
      mostrar(Math.max(0, ini));
      lb.classList.add("aberto");
      document.body.classList.add("lb-aberto");
    });
    lb.querySelector(".lb-fechar").addEventListener("click", fechar);
    lb.querySelector(".lb-ant").addEventListener("click", () => mostrar(i - 1));
    lb.querySelector(".lb-prox").addEventListener("click", () =>
      mostrar(i + 1),
    );
    lb.addEventListener("click", (e) => {
      if (e.target === lb) fechar();
    });
    swipe(
      lb,
      () => mostrar(i + 1),
      () => mostrar(i - 1),
    );
    addEventListener("keydown", (e) => {
      if (!lb.classList.contains("aberto")) return;
      if (e.key === "Escape") fechar();
      if (e.key === "ArrowLeft") mostrar(i - 1);
      if (e.key === "ArrowRight") mostrar(i + 1);
    });
  }

  const compFav = document.querySelector("[data-compartilhar-fav]");
  if (compFav) {
    compFav.addEventListener("click", async () => {
      const texto = compFav.dataset.texto;
      try {
        if (navigator.share) await navigator.share({ text: texto });
        else
          window.open(
            "https://wa.me/?text=" + encodeURIComponent(texto),
            "_blank",
            "noopener",
          );
      } catch (e) {}
    });
  }

  const sugUrl = (document.body.dataset.favUrl || "").replace(
    "favoritar.php",
    "sugestoes.php",
  );
  if (sugUrl) {
    document
      .querySelectorAll(".busca-painel input[name=q], .busca-cat input[name=q]")
      .forEach((input) => {
        const form = input.form;
        const caixa = document.createElement("div");
        caixa.className = "sugestoes";
        caixa.hidden = true;
        form.appendChild(caixa);
        let timer,
          reqId = 0,
          sel = -1;
        const fechar = () => {
          caixa.hidden = true;
          sel = -1;
        };
        const marcar = (n) => {
          const itens = caixa.querySelectorAll("a");
          if (!itens.length) return;
          sel = (n + itens.length) % itens.length;
          itens.forEach((a, k) => a.classList.toggle("sel", k === sel));
        };
        const render = (dados, q) => {
          caixa.textContent = "";
          if (!dados.length) {
            fechar();
            return;
          }
          dados.forEach((d) => {
            const a = document.createElement("a");
            a.href = d.url;
            const im = document.createElement("span");
            im.className = "sug-foto";
            if (d.foto) {
              const i = document.createElement("img");
              i.src = d.foto;
              i.alt = "";
              im.appendChild(i);
            }
            const tx = document.createElement("span");
            tx.className = "sug-texto";
            const n = document.createElement("strong");
            n.textContent = d.nome;
            const m = document.createElement("small");
            m.textContent = d.meta;
            tx.append(n, m);
            a.append(im, tx);
            caixa.appendChild(a);
          });
          const todos = document.createElement("a");
          todos.className = "sug-todos";
          todos.href = form.action + "?q=" + encodeURIComponent(q);
          todos.textContent = "Ver todos os resultados";
          caixa.appendChild(todos);
          caixa.style.left = input.offsetLeft + "px";
          caixa.style.top = input.offsetTop + input.offsetHeight + "px";
          caixa.style.width = input.offsetWidth + "px";
          caixa.hidden = false;
          sel = -1;
        };
        input.addEventListener("input", () => {
          clearTimeout(timer);
          const q = input.value.trim();
          if (q.length < 2) {
            fechar();
            return;
          }
          timer = setTimeout(async () => {
            const id = ++reqId;
            try {
              const r = await fetch(sugUrl + "?q=" + encodeURIComponent(q));
              const dados = await r.json();
              if (id === reqId) render(dados, q);
            } catch (e) {}
          }, 200);
        });
        input.addEventListener("keydown", (e) => {
          if (caixa.hidden) return;
          if (e.key === "ArrowDown") {
            e.preventDefault();
            marcar(sel + 1);
          } else if (e.key === "ArrowUp") {
            e.preventDefault();
            marcar(sel - 1);
          } else if (e.key === "Enter" && sel >= 0) {
            e.preventDefault();
            caixa.querySelectorAll("a")[sel].click();
          } else if (e.key === "Escape") fechar();
        });
        document.addEventListener("click", (e) => {
          if (!form.contains(e.target)) fechar();
        });
      });
  }
});

const avisoCookies = document.getElementById("aviso-cookies");
if (avisoCookies) {
  let visto = false;
  try {
    visto = localStorage.getItem("cookies_ok") === "1";
  } catch (e) {}
  avisoCookies.hidden = visto;
  avisoCookies
    .querySelector("[data-cookies-ok]")
    .addEventListener("click", () => {
      try {
        localStorage.setItem("cookies_ok", "1");
      } catch (e) {}
      avisoCookies.hidden = true;
    });
}
