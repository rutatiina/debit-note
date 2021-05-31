
const Index = () => import('./components/l-limitless-bs4/Index');
const Form = () => import('./components/l-limitless-bs4/Form');
const Show = () => import('./components/l-limitless-bs4/Show');
const SideBarLeft = () => import('./components/l-limitless-bs4/SideBarLeft');
const SideBarRight = () => import('./components/l-limitless-bs4/SideBarRight');

const routes = [

    {
        path: '/debit-notes',
        components: {
            default: Index,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Debit Notes',
            metaTags: [
                {
                    name: 'description',
                    content: 'Debit Notes'
                },
                {
                    property: 'og:description',
                    content: 'Debit Notes'
                }
            ]
        }
    },
    {
        path: '/debit-notes/create',
        components: {
            default: Form,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Debit Note :: Create',
            metaTags: [
                {
                    name: 'description',
                    content: 'Create Debit Note'
                },
                {
                    property: 'og:description',
                    content: 'Create Debit Note'
                }
            ]
        }
    },
    {
        path: '/debit-notes/:id',
        components: {
            default: Show,
            'sidebar-left': SideBarLeft,
            'sidebar-right': SideBarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Debit Note',
            metaTags: [
                {
                    name: 'description',
                    content: 'Debit Note'
                },
                {
                    property: 'og:description',
                    content: 'Debit Note'
                }
            ]
        }
    },
    {
        path: '/debit-notes/:id/copy',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Debit Note :: Copy',
            metaTags: [
                {
                    name: 'description',
                    content: 'Copy Debit Note'
                },
                {
                    property: 'og:description',
                    content: 'Copy Debit Note'
                }
            ]
        }
    },
    {
        path: '/debit-notes/:id/edit',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Debit Note :: Edit',
            metaTags: [
                {
                    name: 'description',
                    content: 'Edit Debit Note'
                },
                {
                    property: 'og:description',
                    content: 'Edit Debit Note'
                }
            ]
        }
    }

]

export default routes
