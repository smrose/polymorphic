document.addEventListener("DOMContentLoaded", () => {
  // UI-only search: prevent navigation for now
  const form = document.querySelector(".topsearch");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const q = form.querySelector('input[name="q"]')?.value?.trim();
      if (q) alert(`Search is not wired yet.\nQuery: ${q}`);
      else alert("Search is not wired yet.");
    });
  }

  // Optional: focus search with "/"
  document.addEventListener("keydown", (e) => {
    if (e.key === "/" && !e.metaKey && !e.ctrlKey && !e.altKey) {
      const input = document.querySelector(".topsearch__input");
      if (input && document.activeElement !== input) {
        e.preventDefault();
        input.focus();
      }
    }
  });
});