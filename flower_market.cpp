#include <iostream>
#include <vector>
#include <string>
#include <map>
#include <fstream>      // ADDED: for flowers.txt (bonus)
#include <algorithm>    // ADDED: for min()
#include <sstream>      // ADDED: for price formatting
#include <iomanip>
using namespace std;

struct Flower {
	string display_name;
	int lot_size;
};

// Default data (used if flowers.txt is not found)
map<string, Flower> flowerData = {
	{"rose", {"Rose", 100}},
	{"tulip", {"Tulip", 50}},
	{"gerbera", {"Gerbera", 10}}
};

// BONUS: load flower data from flowers.txt
// File format, one flower per line:   rose Rose 100
void loadFlowerData() {
	ifstream file("flowers.txt");
	if (!file) return;                       // keep default data
	map<string, Flower> loaded;
	string key, name;
	int lot;
	while (file >> key >> name >> lot) loaded[key] = {name, lot};
	if (!loaded.empty()) {
		flowerData = loaded;
		cout << "Loaded " << flowerData.size() << " flowers from flowers.txt\n";
	}
}

// ADDED: print price as 50.00 instead of 50.000000
string fmt(double v) { ostringstream o; o << fixed << setprecision(2) << v; return o.str(); }

struct Order {
	string trader_id;
	string symbol;
	int qty;
	double price;
	int side; // 1 = BUY, 2 = SELL
};

// ADDED: a match between a buyer and a seller, waiting for confirmation
struct Trade {
	int id = 0;
	string buyer_id, seller_id, symbol;
	int qty;
	double price;
	bool confirmed = false;
};

class OrderBook {
public:
	OrderBook() : symbol("") {} // Default constructor for map
	OrderBook(string symbol) : symbol(symbol) {}

	// Matching logic: returns the trades created by this order
	vector<Trade> addOrder(Order order) {
		vector<Trade> trades;
		// BUY looks at SELL side, SELL looks at BUY side
		vector<Order> &opposite = (order.side == 1) ? sell_orders : buy_orders;

		while (order.qty > 0 && !opposite.empty()) {
			Order &best = opposite.front();   // best price, oldest first
			bool canMatch = (order.side == 1) ? order.price >= best.price   // buyer pays enough
			                                  : order.price <= best.price;  // seller asks low enough
			if (!canMatch) break;

			int q = min(order.qty, best.qty);
			Trade t;
			t.buyer_id  = (order.side == 1) ? order.trader_id : best.trader_id;
			t.seller_id = (order.side == 2) ? order.trader_id : best.trader_id;
			t.symbol = symbol;
			t.qty = q;
			t.price = best.price;              // trade at the price already in the book
			trades.push_back(t);

			order.qty -= q;
			best.qty -= q;
			if (best.qty == 0) opposite.erase(opposite.begin());
		}

		// Remaining quantity waits in the book (sorted by price, then time)
		if (order.qty > 0) {
			vector<Order> &own = (order.side == 1) ? buy_orders : sell_orders;
			auto it = own.begin();
			// BUY: highest price first, SELL: lowest price first; same price -> after older orders
			while (it != own.end() &&
			       (order.side == 1 ? it->price >= order.price : it->price <= order.price))
				++it;
			own.insert(it, order);
		}
		return trades;
	}

	void viewOrders() {
		cout << "\nOrder Book for " << symbol << ":\n";
		cout << "BUY Orders:\n";
		for (auto &o : buy_orders)
			cout << o.trader_id << " " << o.qty << "@" << o.price << "\n";
		cout << "SELL Orders:\n";
		for (auto &o : sell_orders)
			cout << o.trader_id << " " << o.qty << "@" << o.price << "\n";
	}

private:
	string symbol;
	vector<Order> buy_orders;
	vector<Order> sell_orders;
};

class Market {
public:
	void marketOpen() { market_open = true; cout << "Market is now opened\n"; }

	void marketClose() {
		market_open = false;
		cout << "Market is now closed\n";
		// ADDED: unconfirmed matches and waiting orders expire at close
		for (auto &p : trades) {
			Trade &t = p.second;
			if (!t.confirmed) {
				string msg = "Trade #" + to_string(t.id) + " EXPIRED (not confirmed before market close)";
				notify(t.buyer_id, msg);
				notify(t.seller_id, msg);
			}
		}
		for (auto it = trades.begin(); it != trades.end();)
			it = it->second.confirmed ? next(it) : trades.erase(it);
		orderBooks.clear();
	}

	void addOrder(Order order) {
		// Check if market is open, reject if closed
		if (!market_open) {
			notify(order.trader_id, "Order REJECTED: market is closed");
			return;
		}
		// Validate the order
		if (!validateOrder(order)) return;

		// Create OrderBook for symbol if it doesn't exist
		if (orderBooks.find(order.symbol) == orderBooks.end())
			orderBooks[order.symbol] = OrderBook(flowerData[order.symbol].display_name);

		notify(order.trader_id, string("Order ACCEPTED: ") + (order.side == 1 ? "BUY " : "SELL ") +
		                        to_string(order.qty) + " " + flowerData[order.symbol].display_name +
		                        " @ " + fmt(order.price));

		// Route the order to the correct OrderBook
		vector<Trade> newTrades = orderBooks[order.symbol].addOrder(order);

		// Notify BOTH sides of every match
		for (auto &t : newTrades) {
			t.id = next_trade_id++;
			trades[t.id] = t;
			string msg = "MATCH FOUND - Trade #" + to_string(t.id) + ": " + to_string(t.qty) + " " +
			             t.symbol + " @ " + fmt(t.price) + " | Buyer: " + t.buyer_id +
			             ", Seller: " + t.seller_id + " | waiting for confirmation";
			notify(t.buyer_id, msg);
			notify(t.seller_id, msg);
		}
	}

	// ADDED: either side can confirm, then both are notified
	void confirmTrade(string trader_id, int trade_id) {
		if (trades.find(trade_id) == trades.end()) { cout << "Trade not found\n"; return; }
		Trade &t = trades[trade_id];
		if (trader_id != t.buyer_id && trader_id != t.seller_id) {
			cout << "You are not part of this trade\n"; return;
		}
		if (t.confirmed) { cout << "Trade already confirmed\n"; return; }

		t.confirmed = true;
		string msg = "CONFIRMED by " + trader_id + " - Trade #" + to_string(t.id) + ": " +
		             to_string(t.qty) + " " + t.symbol + " @ " + fmt(t.price) +
		             " | Total: " + fmt(t.qty * t.price);
		notify(t.buyer_id, msg);
		notify(t.seller_id, msg);
	}

	// ADDED: show a trader's notifications
	void viewNotifications(string trader_id) {
		cout << "\nNotifications for " << trader_id << ":\n";
		if (inbox[trader_id].empty()) cout << "(none)\n";
		for (auto &m : inbox[trader_id]) cout << "- " << m << "\n";
	}

	void viewOrders() {
		for (auto &pair : orderBooks) {
			pair.second.viewOrders();
		}
	}

private:
	bool market_open = false;
	map<string, OrderBook> orderBooks;
	map<int, Trade> trades;                    // ADDED
	map<string, vector<string>> inbox;         // ADDED: notifications per trader
	int next_trade_id = 1;                     // ADDED

	void notify(string trader_id, string msg) {
		inbox[trader_id].push_back(msg);
		cout << "  >> [" << trader_id << "] " << msg << "\n";
	}

	bool validateOrder(Order &order) {
		// Validate symbol exists in flowerData (case-insensitive: "Rose" -> "rose")
		for (auto &c : order.symbol) c = tolower(c);
		if (flowerData.find(order.symbol) == flowerData.end()) {
			notify(order.trader_id, "Order REJECTED: invalid flower '" + order.symbol + "'");
			return false;
		}
		// Validate quantity is positive and multiple of lot_size
		int lot = flowerData[order.symbol].lot_size;
		if (order.qty <= 0 || order.qty % lot != 0) {
			notify(order.trader_id, "Order REJECTED: quantity must be a positive multiple of " + to_string(lot));
			return false;
		}
		// Validate price is positive
		if (order.price <= 0) {
			notify(order.trader_id, "Order REJECTED: price must be positive");
			return false;
		}
		// Validate side is either 1 (BUY) or 2 (SELL)
		if (order.side != 1 && order.side != 2) {
			notify(order.trader_id, "Order REJECTED: side must be 1 (BUY) or 2 (SELL)");
			return false;
		}
		return true;
	}
};

int main() {
	loadFlowerData();   // ADDED (bonus)
	Market market;
	int choice;
	while (true) {
		cout << "\n1. Market Open\n2. Market Close\n3. Add Order\n4. View Orders"
		        "\n5. Confirm Trade\n6. View Notifications\n7. Exit\nChoice: ";
		cin >> choice;
		if (choice == 1) market.marketOpen();
		else if (choice == 2) market.marketClose();
		else if (choice == 3) {
			Order o;
			cout << "Trader ID: "; cin >> o.trader_id;
			cout << "Symbol: "; cin >> o.symbol;
			cout << "Quantity: "; cin >> o.qty;
			cout << "Price: "; cin >> o.price;
			cout << "Side (1=BUY, 2=SELL): "; cin >> o.side;
			market.addOrder(o);
		}
		else if (choice == 4) market.viewOrders();
		else if (choice == 5) {                       // ADDED
			string id; int tid;
			cout << "Trader ID: "; cin >> id;
			cout << "Trade ID: "; cin >> tid;
			market.confirmTrade(id, tid);
		}
		else if (choice == 6) {                       // ADDED
			string id;
			cout << "Trader ID: "; cin >> id;
			market.viewNotifications(id);
		}
		else break;
	}
	return 0;
}
