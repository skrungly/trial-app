import { createRouter, createWebHistory } from 'vue-router'
import ShopLayout from '../layouts/ShopLayout.vue'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // The customer-facing shop.
    {
      path: '/',
      component: ShopLayout,
      children: [
        {
          path: '',
          name: 'home',
          component: HomeView,
        },
        {
          path: 'cart',
          name: 'cart',
          component: () => import('../views/CartView.vue'),
        },
        {
          path: 'orders',
          name: 'orders',
          component: () => import('../views/OrdersView.vue'),
        },
      ],
    },
    // Internal pages for running the factory floor, with their own navbar.
    {
      path: '/factory',
      component: () => import('../layouts/FactoryLayout.vue'),
      children: [
        { path: '', redirect: { name: 'factory-picking' } },
        {
          path: 'picking',
          name: 'factory-picking',
          component: () => import('../views/factory/PickingView.vue'),
        },
      ],
    },
  ],
})

export default router
