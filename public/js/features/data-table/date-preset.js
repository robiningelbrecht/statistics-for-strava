export class DatePreset {
    static resolve(presetName) {
        const now = new Date();
        const today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));

        switch (presetName) {
            case 'this-week': {
                const from = new Date(today);
                const day = today.getUTCDay();
                from.setUTCDate(from.getUTCDate() - (day === 0 ? 6 : day - 1));
                return {from, to: new Date(today)};
            }
            case 'last-week': {
                const day = today.getUTCDay();
                const from = new Date(today);
                from.setUTCDate(from.getUTCDate() - (day === 0 ? 6 : day - 1) - 7);
                const to = new Date(from);
                to.setUTCDate(to.getUTCDate() + 6);
                return {from, to};
            }
            case 'this-month':
                return {
                    from: new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1)),
                    to: new Date(today),
                };
            case 'last-month':
                return {
                    from: new Date(Date.UTC(now.getFullYear(), now.getMonth() - 1, 1)),
                    to: new Date(Date.UTC(now.getFullYear(), now.getMonth(), 0)),
                };
            case 'last-30-days': {
                const from = new Date(today);
                from.setUTCDate(from.getUTCDate() - 29);
                return {from, to: new Date(today)};
            }
            default:
                return null;
        }
    }
}
