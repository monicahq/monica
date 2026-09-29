/**
 * A page of the product guide: the heading being read, the filter typed over
 * the list of pages, and whether that list is open on a narrow screen.
 */
export default ({ sections }) => ({
    current: sections[0] ?? null,
    query: '',
    open: false,

    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                this.current = entry.target.id;
            });
        }, { rootMargin: '-80px 0px -70% 0px' });

        sections.forEach((id) => {
            const heading = document.getElementById(id);

            if (heading) {
                observer.observe(heading);
            }
        });
    },

    matches(title) {
        return this.query.trim() === '' || title.includes(this.query.trim().toLowerCase());
    },
});
