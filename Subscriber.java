public class Subscriber implements Observer {
    private final String name;
    public Subscriber(String name){
        this.name = name;
    }
    @Override
    public void update(String title, String content, String date) {
        System.out.println("订阅者" + name + "收到新闻：" + content);

    }
    @Override
    public String toString(){
        return name;
    }
}
